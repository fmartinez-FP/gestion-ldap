<?php

namespace App\Livewire\Usuarios;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\LdapService;
use App\Models\Aplicacion;
use Illuminate\Support\Facades\Mail;

class ListaUsuarios extends Component
{
    use WithPagination;

    public string $buscar = '';
    public string $filtroApp = '';
    public string $filtroActivo = '';
    public ?string $confirmarEliminar = null;
    public bool $mostrarExito = false;
    public string $mensajeExito = '';

    protected LdapService $ldap;

    public function boot(LdapService $ldap)
    {
        $this->ldap = $ldap;
    }

    public function updatingBuscar()   { $this->resetPage(); }
    public function updatingFiltroApp() { $this->resetPage(); }

    public function toggleActivo(string $uid): void
    {
        $user = $this->ldap->getUser($uid);
        if (!$user) return;

        $this->ldap->setUserActive($uid, !$user['activo']);
        $this->flash('Estado actualizado correctamente.');
    }

    public function confirmarDelete(string $uid): void
    {
        $this->confirmarEliminar = $uid;
    }

    public function cancelarDelete(): void
    {
        $this->confirmarEliminar = null;
    }

    public function eliminarUsuario(): void
    {
        if (!$this->confirmarEliminar) return;

        $this->ldap->deleteUser($this->confirmarEliminar);
        $this->confirmarEliminar = null;
        $this->flash('Usuario eliminado correctamente.');
    }

    public function resetearContrasena(string $uid): void
    {
        $user = $this->ldap->getUser($uid);
        if (!$user) return;

        $this->ldap->resetPassword($uid);

        // Enviar correo con contraseña temporal
        try {
            $tempPass = $uid . '1234';
            Mail::send('emails.credenciales', [
                'nombre'    => $user['nombre_completo'],
                'username'  => $uid,
                'password'  => $tempPass,
            ], function ($m) use ($user) {
                $m->to($user['mail'])
                  ->subject('IES Pacífico — Contraseña restablecida');
            });
        } catch (\Exception $e) {
            // El mail falla silenciosamente, la contraseña ya se reseteó
        }

        $this->flash("Contraseña restablecida para {$uid}.");
    }

    private function flash(string $mensaje): void
    {
        $this->mensajeExito = $mensaje;
        $this->mostrarExito = true;
    }

    public function render()
    {
        $todosUsuarios = $this->ldap->getAllUsers();
        $apps          = Aplicacion::where('activo', true)->orderBy('orden')->get();

        // Filtros
        $usuarios = collect($todosUsuarios)->filter(function ($u) {
            $matchBuscar = empty($this->buscar) ||
                str_contains(strtolower($u['nombre_completo']), strtolower($this->buscar)) ||
                str_contains(strtolower($u['uid']), strtolower($this->buscar)) ||
                str_contains(strtolower($u['mail']), strtolower($this->buscar));

            $matchApp = empty($this->filtroApp) || in_array($this->filtroApp, $u['grupos']);

            $matchActivo = $this->filtroActivo === '' ||
                ($this->filtroActivo === '1' && $u['activo']) ||
                ($this->filtroActivo === '0' && !$u['activo']);

            return $matchBuscar && $matchApp && $matchActivo;
        })->values();

        $stats = $this->ldap->getStats();

        return view('livewire.usuarios.lista', [
            'usuarios' => $usuarios,
            'apps'     => $apps,
            'stats'    => $stats,
        ]);
    }
}
