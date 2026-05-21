<?php

namespace App\Livewire\Portal;

use App\Models\Aplicacion;
use Livewire\Component;

class UserDashboard extends Component
{
    public array $user         = [];
    public array $aplicaciones = [];

    public function mount(): void
    {
        $this->user = session('ldap_user', []);

        $grupos = $this->user['grupos'] ?? [];

        // Filtra aplicaciones activas cuyo codigo coincide con algún grupo LDAP del usuario.
        // aplicaciones.codigo = CN del grupo (ffe, guardias, inventarios).
        $this->aplicaciones = empty($grupos)
            ? []
            : Aplicacion::query()
                ->where('activo', true)
                ->whereIn('codigo', $grupos)
                ->orderBy('orden')
                ->get()
                ->toArray();
    }

    public function render()
    {
        return view('livewire.portal.user-dashboard');
    }
}
