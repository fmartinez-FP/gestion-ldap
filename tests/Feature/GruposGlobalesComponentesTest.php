<?php

namespace Tests\Feature;

use App\Livewire\Aplicaciones\GestorApps;
use App\Livewire\Aplicaciones\GruposGlobales;
use App\Livewire\Portal\UserDashboard;
use App\Livewire\Usuarios\ImportarCSV;
use App\Livewire\Usuarios\ListaUsuarios;
use App\Models\Admin;
use App\Models\Aplicacion;
use App\Services\LdapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Componentes de grupos globales con LdapService simulado (sin servidor LDAP).
 */
class GruposGlobalesComponentesTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAdmin(): void
    {
        $admin = Admin::create(['username' => 'tester', 'password' => 'secreto-123']);
        $this->actingAs($admin, 'admin');
    }

    private function grupoGlobal(string $cn, array $miembros): array
    {
        return [
            'cn'          => $cn,
            'description' => "Grupo {$cn}",
            'global'      => true,
            'memberDns'   => array_map(fn($u) => "uid={$u},ou=users,dc=iespacifico,dc=es", $miembros),
            'miembros'    => $miembros,
        ];
    }

    // ── Crear grupo global (GestorApps) ──────────────────────────────────────

    public function test_crea_grupo_global_sin_fila_en_aplicaciones_ni_url(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('groupExists')->with('admins')->andReturn(false);
            $m->shouldReceive('createGlobalGroup')->once()
                ->with('admins', 'Administradores de todas las aplicaciones', 'admin')->andReturn(true);
            $m->shouldNotReceive('createGroup');
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')
            ->set('tipo', 'global')
            ->set('codigo', 'admins')
            ->set('descripcion', 'Administradores de todas las aplicaciones')
            ->set('miembroInicial', 'admin')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('mostrarModal', false)
            ->assertDispatched('grupo-global-actualizado');

        $this->assertSame(0, Aplicacion::count(), 'Un grupo global no debe crear una aplicación');
    }

    public static function nombresInvalidos(): array
    {
        return [
            'espacio'          => ['mis admins'],
            'punto'            => ['a.b'],
            'coma'             => ['admins,ou=users'],
            'igual'            => ['cn=admins'],
            'asterisco'        => ['*'],
            'tilde'            => ['administración'],
            'más de 64 chars'  => [str_repeat('a', 65)],
        ];
    }

    #[DataProvider('nombresInvalidos')]
    public function test_rechaza_nombres_de_grupo_invalidos(string $nombre): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('groupExists')->andReturn(false);
            $m->shouldNotReceive('createGlobalGroup');
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')
            ->set('tipo', 'global')
            ->set('codigo', $nombre)
            ->set('descripcion', 'x')
            ->set('miembroInicial', 'admin')
            ->call('guardar')
            ->assertHasErrors(['codigo']);
    }

    public function test_rechaza_grupo_que_ya_existe_en_ldap(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('groupExists')->with('Admins')->andReturn(true);
            $m->shouldNotReceive('createGlobalGroup');
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')->set('tipo', 'global')
            ->set('codigo', 'Admins')->set('descripcion', 'x')->set('miembroInicial', 'admin')
            ->call('guardar')
            ->assertHasErrors(['codigo']);
    }

    public function test_rechaza_nombre_que_coincide_con_una_aplicacion_sin_distinguir_mayusculas(): void
    {
        $this->actuarComoAdmin();
        Aplicacion::create(['nombre' => 'Wekan', 'codigo' => 'Wekan']);
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('groupExists')->andReturn(false);
            $m->shouldNotReceive('createGlobalGroup');
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')->set('tipo', 'global')
            ->set('codigo', 'wekan')->set('descripcion', 'x')->set('miembroInicial', 'admin')
            ->call('guardar')
            ->assertHasErrors(['codigo']);
    }

    public function test_exige_descripcion_y_miembro_inicial(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('groupExists')->andReturn(false);
            $m->shouldNotReceive('createGlobalGroup');
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')->set('tipo', 'global')->set('codigo', 'admins')
            ->call('guardar')
            ->assertHasErrors(['descripcion', 'miembroInicial']);
    }

    public function test_muestra_el_error_del_servicio_si_el_usuario_no_existe(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('groupExists')->andReturn(false);
            $m->shouldReceive('createGlobalGroup')->andThrow(new \InvalidArgumentException("El usuario 'fantasma' no existe."));
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')->set('tipo', 'global')
            ->set('codigo', 'admins')->set('descripcion', 'x')->set('miembroInicial', 'fantasma')
            ->call('guardar')
            ->assertHasErrors(['codigo']);
    }

    public function test_una_aplicacion_no_puede_reutilizar_el_nombre_de_un_grupo_global(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGroup')->with('admins')->andReturn($this->grupoGlobal('admins', ['admin']));
            $m->shouldNotReceive('createGroup');
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')
            ->set('nombre', 'Admins app')->set('codigo', 'admins')
            ->call('guardar')
            ->assertHasErrors(['codigo']);

        $this->assertSame(0, Aplicacion::count());
    }

    public function test_crear_una_aplicacion_normal_sigue_funcionando_con_y_sin_url(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGroup')->andReturn(null);
            $m->shouldReceive('createGroup')->twice()->andReturn(true);
        });

        Livewire::test(GestorApps::class)
            ->call('nueva')->set('nombre', 'Tutorías')->set('codigo', 'tutorias')
            ->set('url', 'https://tutorias.example.org')->call('guardar')->assertHasNoErrors();

        Livewire::test(GestorApps::class)
            ->call('nueva')->set('nombre', 'Sin URL')->set('codigo', 'sinurl')
            ->call('guardar')->assertHasNoErrors();

        $this->assertSame(2, Aplicacion::count());
    }

    // ── Solo administradores del panel ───────────────────────────────────────

    public function test_sin_sesion_de_administrador_no_se_puede_crear_ni_gestionar(): void
    {
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGlobalGroups')->andReturn([]);
            $m->shouldNotReceive('createGlobalGroup');
            $m->shouldNotReceive('addGlobalGroupMember');
            $m->shouldNotReceive('removeGlobalGroupMember');
        });

        Livewire::test(GestorApps::class)->call('guardar')->assertForbidden();
        Livewire::test(GestorApps::class)->call('nueva')->assertForbidden();

        // Un componente nuevo por cada intento: tras un 403 el anterior ya no sirve
        Livewire::test(GruposGlobales::class)->call('gestionar', 'admins')->assertForbidden();
        Livewire::test(GruposGlobales::class)->set('grupoAbierto', 'admins')->set('uidNuevo', 'x')
            ->call('anadirMiembro')->assertForbidden();
        Livewire::test(GruposGlobales::class)->set('grupoAbierto', 'admins')
            ->call('quitarMiembro', 'admin')->assertForbidden();
    }

    // ── Gestión de miembros (GruposGlobales) ─────────────────────────────────

    public function test_lista_los_grupos_globales_con_la_marca_global(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGlobalGroups')->andReturn([$this->grupoGlobal('admins', ['admin'])]);
        });

        Livewire::test(GruposGlobales::class)
            ->assertSee('admins')
            ->assertSee('Global')
            ->assertSee('1 miembro');
    }

    public function test_no_abre_la_gestion_de_un_grupo_que_no_es_global(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGroup')->with('ffe')->andReturn(
                ['cn' => 'ffe', 'description' => '', 'global' => false, 'memberDns' => [], 'miembros' => []]
            );
            $m->shouldReceive('getGlobalGroups')->andReturn([]);
        });

        Livewire::test(GruposGlobales::class)
            ->call('gestionar', 'ffe')
            ->assertSet('grupoAbierto', null);
    }

    public function test_anadir_y_quitar_miembros_delegan_en_el_servicio(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGlobalGroups')->andReturn([$this->grupoGlobal('admins', ['admin', 'jperez'])]);
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('addGlobalGroupMember')->once()->with('admins', 'mgarcia')->andReturn(true);
            $m->shouldReceive('removeGlobalGroupMember')->once()->with('admins', 'jperez')->andReturn(true);
        });

        Livewire::test(GruposGlobales::class)
            ->set('grupoAbierto', 'admins')
            ->set('uidNuevo', 'mgarcia')->call('anadirMiembro')->assertSet('error', '')
            ->call('quitarMiembro', 'jperez')->assertSet('error', '');
    }

    public function test_muestra_el_error_al_quitar_el_ultimo_miembro(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGlobalGroups')->andReturn([$this->grupoGlobal('admins', ['admin'])]);
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('removeGlobalGroupMember')->andThrow(new \InvalidArgumentException('No se puede quitar al último miembro'));
        });

        Livewire::test(GruposGlobales::class)
            ->set('grupoAbierto', 'admins')
            ->call('quitarMiembro', 'admin')
            ->assertSet('error', 'No se puede quitar al último miembro')
            ->assertDontSee('wire:click="quitarMiembro');   // con un solo miembro no se ofrece el botón
    }

    // ── Los grupos globales no son aplicaciones ──────────────────────────────

    public function test_el_lanzador_del_portal_no_muestra_grupos_globales(): void
    {
        Aplicacion::create(['nombre' => 'FFE', 'codigo' => 'ffe', 'activo' => true]);
        session(['ldap_user' => ['grupos' => ['ffe', 'admins']]]);

        $c = Livewire::test(UserDashboard::class);

        $this->assertCount(1, $c->get('aplicaciones'));
        $this->assertSame('ffe', $c->get('aplicaciones')[0]['codigo']);
    }

    public function test_el_csv_no_permite_asignar_grupos_globales(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllGroups')->andReturn([
                ['cn' => 'ffe',    'description' => '', 'miembros' => 3, 'global' => false],
                ['cn' => 'admins', 'description' => '', 'miembros' => 1, 'global' => true],
            ]);
        });

        Livewire::test(ImportarCSV::class)->assertSet('gruposValidos', ['ffe']);
    }

    // ── Borrado de usuarios ──────────────────────────────────────────────────

    public function test_no_se_elimina_al_ultimo_miembro_de_un_grupo_global(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('getStats')->andReturn(['total' => 0, 'activos' => 0, 'inactivos' => 0]);
            $m->shouldReceive('lastMemberGlobalGroups')->with('admin')->andReturn(['admins']);
            $m->shouldNotReceive('deleteUser');
        });

        Livewire::test(ListaUsuarios::class)
            ->call('confirmarDelete', 'admin')
            ->call('eliminarUsuario')
            ->assertSet('confirmarEliminar', null)
            ->assertSet('mostrarExito', false)
            ->assertSee('último miembro del grupo global admins');
    }

    public function test_se_elimina_un_usuario_que_no_es_el_ultimo_miembro(): void
    {
        $this->actuarComoAdmin();
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getAllUsers')->andReturn([]);
            $m->shouldReceive('getStats')->andReturn(['total' => 0, 'activos' => 0, 'inactivos' => 0]);
            $m->shouldReceive('lastMemberGlobalGroups')->with('jperez')->andReturn([]);
            $m->shouldReceive('deleteUser')->once()->with('jperez')->andReturn(true);
        });

        Livewire::test(ListaUsuarios::class)
            ->call('confirmarDelete', 'jperez')
            ->call('eliminarUsuario')
            ->assertSet('mostrarExito', true)
            ->assertSet('mensajeError', '');
    }
}
