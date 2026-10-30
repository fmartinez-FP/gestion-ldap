<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminAuth;
use App\Models\Admin;
use App\Models\Setting;
use App\Services\LdapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Las acciones de Livewire llegan por /livewire/update: AdminAuth debe aplicarse también ahí.
 */
class LivewireAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_auth_es_middleware_persistente_de_livewire(): void
    {
        $this->assertContains(AdminAuth::class, app(PersistentMiddleware::class)->getPersistentMiddleware());
    }

    /** Devuelve el snapshot (JSON) del componente indicado dentro del HTML de la página. */
    private function snapshotDe(string $html, string $nombre): string
    {
        preg_match_all('/wire:snapshot="([^"]*)"/', $html, $m);
        foreach ($m[1] as $raw) {
            $json = html_entity_decode($raw, ENT_QUOTES);
            if (($d = json_decode($json, true)) && ($d['memo']['name'] ?? '') === $nombre) {
                return $json;
            }
        }
        $this->fail("No se encontró el snapshot de {$nombre}");
    }

    private function llamar(string $snapshot, string $metodo)
    {
        return $this->postJson(app('livewire')->getUpdateUri(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates'  => new \stdClass(),
                'calls'    => [['path' => '', 'method' => $metodo, 'params' => []]],
            ]],
        ], ['X-Livewire' => 'true']);
    }

    /** El administrador carga /aplicaciones y devuelve el snapshot válido de un componente. */
    private function snapshotComoAdmin(): string
    {
        $this->mock(LdapService::class, function (MockInterface $m) {
            $m->shouldReceive('getGlobalGroups')->andReturn([]);
            $m->shouldReceive('getAllUsers')->andReturn([]);
        });

        Setting::set('center_configured', '1');   // si no, CheckSetup redirige a /setup
        $admin = Admin::create(['username' => 'tester', 'password' => 'secreto-123']);

        $html = $this->actingAs($admin, 'admin')->get('/aplicaciones')->assertOk()->getContent();
        return $this->snapshotDe($html, 'aplicaciones.grupos-globales');
    }

    public function test_una_peticion_livewire_con_sesion_de_admin_funciona(): void
    {
        $snapshot = $this->snapshotComoAdmin();

        $this->llamar($snapshot, 'cerrar')->assertOk();
    }

    /**
     * Cada test usa su propio ciclo de petición: dentro de uno solo, Livewire recuerda
     * que ya aplicó el middleware a esa ruta y no lo repite (en producción no ocurre).
     */
    public function test_una_peticion_livewire_sin_sesion_de_admin_es_rechazada(): void
    {
        $snapshot = $this->snapshotComoAdmin();

        // Snapshot filtrado/reutilizado por alguien sin sesión de administrador
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->llamar($snapshot, 'cerrar')->assertRedirect(route('login'));
    }
}
