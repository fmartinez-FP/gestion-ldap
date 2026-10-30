<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Las acciones de Livewire llegan por /livewire/update, no por la ruta de la
 * página: se comprueba aquí que quien las ejecuta es administrador del panel.
 */
trait RequiresAdmin
{
    protected function authorizeAdmin(): void
    {
        abort_unless(Auth::guard('admin')->check(), 403, 'Solo los administradores del panel pueden hacer esto.');
    }
}
