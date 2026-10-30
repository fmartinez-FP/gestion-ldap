<?php

namespace Tests\Feature;

use App\Services\LdapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas contra el servidor OpenLDAP real. Solo se ejecutan con LDAP_INTEGRATION=1:
 *
 *   LDAP_INTEGRATION=1 php vendor/bin/phpunit --filter GruposGlobalesLdap
 *
 * Crean un grupo (zztest-xxxxxxxx) y, en algún caso, un usuario temporal
 * (zztest-u-xxxxxxxx) que se borran al terminar. Los grupos de aplicaciones
 * existentes no se tocan; cada test comprueba que siguen igual.
 */
class GruposGlobalesLdapTest extends TestCase
{
    use RefreshDatabase;

    private LdapService $ldap;
    private string $grupo;
    private string $usuarioTmp;
    private array $gruposAntes;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('LDAP_INTEGRATION') !== '1') {
            $this->markTestSkipped('Define LDAP_INTEGRATION=1 para probar contra el servidor LDAP real.');
        }

        $this->ldap        = new LdapService();
        $sufijo            = bin2hex(random_bytes(4));
        $this->grupo       = "zztest-{$sufijo}";
        $this->usuarioTmp  = "zztest-u-{$sufijo}";
        $this->gruposAntes = $this->fotoGruposExistentes();
    }

    protected function tearDown(): void
    {
        if (isset($this->ldap)) {
            try { $this->ldap->deleteGroup($this->grupo); } catch (\Throwable) {}
            try {
                if ($this->ldap->getUser($this->usuarioTmp)) $this->ldap->deleteUser($this->usuarioTmp);
            } catch (\Throwable) {}
        }
        parent::tearDown();
    }

    /** cn => [descripción, nº de miembros, global] de todos los grupos que no son de este test. */
    private function fotoGruposExistentes(): array
    {
        $foto = [];
        foreach ($this->ldap->getAllGroups() as $g) {
            if (!str_starts_with($g['cn'], 'zztest-')) {
                $foto[$g['cn']] = [$g['description'], $g['miembros'], $g['global']];
            }
        }
        ksort($foto);
        return $foto;
    }

    private function assertGruposExistentesIntactos(): void
    {
        $this->assertSame($this->gruposAntes, $this->fotoGruposExistentes(),
            'Los grupos de aplicaciones existentes no deben cambiar');
        foreach ($this->gruposAntes as $cn => [, , $global]) {
            $this->assertFalse($global, "El grupo existente '{$cn}' no debe estar marcado como global");
        }
    }

    private function otroUid(): string
    {
        foreach ($this->ldap->getAllUsers() as $u) {
            if ($u['uid'] !== 'admin' && !str_starts_with($u['uid'], 'zztest-')) return $u['uid'];
        }
        $this->fail('No hay un segundo usuario en el directorio para la prueba');
    }

    private function crearUsuarioTmp(): void
    {
        $this->assertTrue($this->ldap->createUser([
            'uid' => $this->usuarioTmp, 'nombre' => 'Prueba', 'apellidos' => 'Temporal',
            'nombre_completo' => 'Prueba Temporal', 'mail' => "{$this->usuarioTmp}@example.org",
            'password' => 'Temporal-1234', 'activo' => true, 'grupos' => [],
        ]));
    }

    public function test_crea_el_grupo_global_con_admin_y_memberof_aparece_en_el_usuario(): void
    {
        $this->assertTrue($this->ldap->createGlobalGroup($this->grupo, 'Grupo de prueba', 'admin'));

        $g = $this->ldap->getGroup($this->grupo);
        $this->assertTrue($g['global']);
        $this->assertSame('Grupo de prueba', $g['description']);
        $this->assertSame(['admin'], $g['miembros']);

        // El overlay memberof refleja el grupo en el usuario (lo que leen las aplicaciones)
        $this->assertContains($this->grupo, $this->ldap->getUser('admin')['grupos']);

        // Se lista como global y no se confunde con los de aplicación
        $this->assertContains($this->grupo, $this->ldap->getGlobalGroupNames());
        $this->assertSame([$this->grupo], array_values(array_filter(
            array_column(array_filter($this->ldap->getAllGroups(), fn($x) => $x['global']), 'cn'),
            fn($cn) => str_starts_with($cn, 'zztest-')
        )));

        $this->assertGruposExistentesIntactos();
    }

    public function test_valida_nombre_duplicado_y_miembro_inicial(): void
    {
        $this->ldap->createGlobalGroup($this->grupo, 'x', 'admin');

        // LDAP no distingue mayúsculas en cn
        $this->assertTrue($this->ldap->groupExists(strtoupper($this->grupo)));
        $this->expectExceptionObject(new \InvalidArgumentException("Ya existe un grupo llamado '" . strtoupper($this->grupo) . "'."));
        $this->ldap->createGlobalGroup(strtoupper($this->grupo), 'x', 'admin');
    }

    public function test_rechaza_nombres_invalidos_y_usuarios_inexistentes(): void
    {
        foreach (['mis admins', 'a,b=c', 'niño', '*'] as $malo) {
            try {
                $this->ldap->createGlobalGroup($malo, 'x', 'admin');
                $this->fail("Debió rechazar el nombre '{$malo}'");
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->ldap->createGlobalGroup($this->grupo, 'x', 'usuario-que-no-existe');
    }

    public function test_el_nombre_de_un_grupo_de_aplicacion_existente_esta_ocupado(): void
    {
        $existente = $this->gruposAntes ? array_key_first($this->gruposAntes) : null;
        $this->assertNotNull($existente, 'Se esperaba al menos un grupo de aplicación');

        $this->expectException(\InvalidArgumentException::class);
        $this->ldap->createGlobalGroup($existente, 'x', 'admin');
    }

    public function test_anadir_y_quitar_miembros_y_no_se_puede_quitar_el_ultimo(): void
    {
        $otro = $this->otroUid();
        $this->ldap->createGlobalGroup($this->grupo, 'x', 'admin');

        $this->assertTrue($this->ldap->addGlobalGroupMember($this->grupo, $otro));
        $this->assertEqualsCanonicalizing(['admin', $otro], $this->ldap->getGroup($this->grupo)['miembros']);
        $this->assertContains($this->grupo, $this->ldap->getUser($otro)['grupos']);

        try {   // ya es miembro
            $this->ldap->addGlobalGroupMember($this->grupo, $otro);
            $this->fail('Debió rechazar un miembro duplicado');
        } catch (\InvalidArgumentException) {}

        $this->assertTrue($this->ldap->removeGlobalGroupMember($this->grupo, $otro));
        $this->assertNotContains($this->grupo, $this->ldap->getUser($otro)['grupos']);

        // Es el último: se rechaza y el grupo sigue intacto
        try {
            $this->ldap->removeGlobalGroupMember($this->grupo, 'admin');
            $this->fail('Debió impedir quitar el último miembro');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('último miembro', $e->getMessage());
        }
        $this->assertFalse($this->ldap->removeFromGroup('admin', $this->grupo), 'removeFromGroup tampoco debe dejarlo vacío');
        $this->assertSame(['admin'], $this->ldap->getGroup($this->grupo)['miembros']);

        $this->assertGruposExistentesIntactos();
    }

    public function test_no_se_gestionan_como_global_los_grupos_de_aplicacion(): void
    {
        $existente = array_key_first($this->gruposAntes);

        $this->expectException(\InvalidArgumentException::class);
        $this->ldap->addGlobalGroupMember($existente, $this->otroUid());
    }

    public function test_editar_o_crear_un_usuario_no_toca_la_pertenencia_a_grupos_globales(): void
    {
        $this->ldap->createGlobalGroup($this->grupo, 'x', 'admin');
        $this->crearUsuarioTmp();
        $this->assertTrue($this->ldap->addGlobalGroupMember($this->grupo, $this->usuarioTmp));

        // updateUser sin ese grupo en la lista NO lo quita…
        $datos = [
            'nombre' => 'Prueba', 'apellidos' => 'Temporal', 'nombre_completo' => 'Prueba Temporal',
            'mail' => "{$this->usuarioTmp}@example.org", 'activo' => true, 'grupos' => [],
        ];
        $this->assertTrue($this->ldap->updateUser($this->usuarioTmp, $datos));
        $this->assertContains($this->grupo, $this->ldap->getUser($this->usuarioTmp)['grupos']);

        // …y quitarlo del grupo y reeditar con él en la lista tampoco lo vuelve a añadir
        $this->ldap->removeGlobalGroupMember($this->grupo, $this->usuarioTmp);
        $this->assertTrue($this->ldap->updateUser($this->usuarioTmp, $datos + ['grupos' => [$this->grupo]]));
        $this->assertNotContains($this->grupo, $this->ldap->getUser($this->usuarioTmp)['grupos']);

        $this->assertGruposExistentesIntactos();
    }

    public function test_createUser_ignora_grupos_globales_en_la_lista_de_grupos(): void
    {
        $this->ldap->createGlobalGroup($this->grupo, 'x', 'admin');

        $this->assertTrue($this->ldap->createUser([
            'uid' => $this->usuarioTmp, 'nombre' => 'Prueba', 'apellidos' => 'Temporal',
            'nombre_completo' => 'Prueba Temporal', 'mail' => "{$this->usuarioTmp}@example.org",
            'password' => 'Temporal-1234', 'activo' => true,
            'grupos' => [strtoupper($this->grupo)],   // p. ej. una fila de CSV con "admins"
        ]));

        $this->assertNotContains($this->grupo, $this->ldap->getUser($this->usuarioTmp)['grupos']);
        $this->assertSame(['admin'], $this->ldap->getGroup($this->grupo)['miembros']);
    }

    public function test_no_se_borra_al_ultimo_miembro_de_un_grupo_global_pero_si_a_otro(): void
    {
        $this->crearUsuarioTmp();
        $this->ldap->createGlobalGroup($this->grupo, 'x', $this->usuarioTmp);   // único miembro

        $this->assertSame([$this->grupo], $this->ldap->lastMemberGlobalGroups($this->usuarioTmp));
        $this->assertFalse($this->ldap->deleteUser($this->usuarioTmp), 'No debe borrar al último miembro');
        $this->assertNotNull($this->ldap->getUser($this->usuarioTmp), 'El usuario debe seguir existiendo');

        // Con otro miembro ya se puede borrar, y el grupo conserva al resto
        $this->ldap->addGlobalGroupMember($this->grupo, 'admin');
        $this->assertSame([], $this->ldap->lastMemberGlobalGroups($this->usuarioTmp));
        $this->assertTrue($this->ldap->deleteUser($this->usuarioTmp));
        $this->assertNull($this->ldap->getUser($this->usuarioTmp));
        $this->assertSame(['admin'], $this->ldap->getGroup($this->grupo)['miembros']);

        $this->assertGruposExistentesIntactos();
    }
}
