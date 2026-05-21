<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LdapPasswordResetController;

// ── Autenticación ──────────────────────────────────────────────────────────────
Route::get('/',       [AuthController::class, 'showLogin'])->name('login');
Route::get('/login',  [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:login');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ── Recuperación de contraseña LDAP (rutas públicas) ──────────────────────────
Route::get('/password/reset',         [LdapPasswordResetController::class, 'showRequestForm'])->name('ldap.password.request');
Route::post('/password/email',        [LdapPasswordResetController::class, 'sendResetLink'])->name('ldap.password.email');
Route::get('/password/reset/{token}', [LdapPasswordResetController::class, 'showResetForm'])->name('ldap.password.reset');
Route::post('/password/reset',        [LdapPasswordResetController::class, 'reset'])->name('ldap.password.update');


// ── Wizard de configuración inicial ───────────────────────────────────────────
Route::get('/setup', fn() => view('setup'))->name('setup');

// ── Panel de administración ────────────────────────────────────────────────────
Route::middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
    Route::get('/usuarios',              fn() => view('livewire.usuarios.index'))->name('usuarios.index');
    Route::get('/usuarios/nuevo',        fn() => view('livewire.usuarios.form'))->name('usuarios.crear');
    Route::get('/usuarios/importar',      fn() => view('livewire.usuarios.importar'))->name('usuarios.importar');
    Route::get('/usuarios/plantilla-csv', function () {
        $contenido = "nombre,apellidos,email,grupos\n";
        $contenido .= "Juan,García López,jgarcia@educa.madrid.org,ffe;guardias\n";
        $contenido .= "María,Martínez Ruiz,mmartinez@educa.madrid.org,inventarios\n";
        return response($contenido, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_usuarios.csv"',
        ]);
    })->name('usuarios.plantilla');
    Route::get('/usuarios/{uid}/editar', fn(string $uid) => view('livewire.usuarios.form', compact('uid')))->name('usuarios.editar');
    Route::get('/aplicaciones',          fn() => view('livewire.aplicaciones.index'))->name('aplicaciones.index');
    Route::get('/configuracion',         fn() => view('livewire.configuracion.index'))->name('configuracion');
    Route::get('/audit',              fn() => view('livewire.audit-logs.index'))->name('audit.index');
    Route::get('/restauracion', function () {
        $dirs = glob('/opt/backups/????-??-??', GLOB_ONLYDIR) ?: [];
        rsort($dirs);
        $backups = [];
        foreach (array_slice($dirs, 0, 10) as $dir) {
            $ldifs  = glob($dir . '/ldap_*.ldif.gz') ?: [];
            $mysqls = glob($dir . '/gestion_ldap_panel_*.sql.gz') ?: [];
            $total  = array_sum(array_map('filesize', array_merge($ldifs, $mysqls)));
            $backups[] = [
                'fecha' => basename($dir),
                'ldif'  => $ldifs  ? basename($ldifs[0])  . ' (' . round(filesize($ldifs[0])  / 1024) . ' KB)' : '—',
                'mysql' => $mysqls ? basename($mysqls[0]) . ' (' . round(filesize($mysqls[0]) / 1024) . ' KB)' : '—',
                'total' => round($total / 1024) . ' KB',
            ];
        }
        $du = shell_exec('du -sh /opt/backups 2>/dev/null') ?: '';
        preg_match('/^([\d.]+\w+)/', trim($du), $m);
        return view('livewire.restauracion.index', [
            'backups'        => $backups,
            'numBackups'     => count($dirs),
            'ultimoBackup'   => $dirs ? basename($dirs[0]) : 'Nunca',
            'espacioBackups' => $m[1] ?? 'N/D',
        ]);
    })->name('restauracion.index');
    Route::get('/restauracion/descargar', function () {
        return response()->download('/opt/docs/restauracion.md',
            'restauracion_ldap_' . date('Ymd') . '.md',
            ['Content-Type' => 'text/markdown']);
    })->name('restauracion.descargar');
    Route::get('/usuarios/exportar-ldif', function () {
        $ldap     = new \App\Services\LdapService();
        $contenido = $ldap->exportToLdif();
        $filename  = 'iespacifico_ldap_' . date('Ymd_His') . '.ldif';
        \App\Models\AuditLog::record('export_ldif', null, ['filename' => $filename]);
        return response($contenido, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    })->name('usuarios.exportar-ldif');
    Route::get('/admin/cambiar-password', fn() => view('admin.change-password'))->name('admin.password');
});

// ── Portal de usuario LDAP ─────────────────────────────────────────────────────
Route::middleware(\App\Http\Middleware\LdapUserAuth::class)
    ->prefix('portal')->name('portal.')
    ->group(function () {
        Route::get('/', function () {
            // Guardia: si ppolicy marcó cambio obligatorio, no permitir acceso al dashboard
            if (session('ldap_must_change_password')) {
                return redirect()->route('portal.password');
            }
            return view('portal.dashboard');
        })->name('dashboard');
        Route::get('/cambiar-password', fn() => view('portal.change-password'))->name('password');
        Route::get('/password-changed', function () {
            session()->forget('ldap_must_change_password');
            return redirect()->route('portal.dashboard');
        })->name('password.done');
    });
