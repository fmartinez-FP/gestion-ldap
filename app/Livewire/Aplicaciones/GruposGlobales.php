<?php

namespace App\Livewire\Aplicaciones;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Livewire\Concerns\RequiresAdmin;
use App\Services\LdapService;

/**
 * Gestión de grupos globales (sin aplicación): listado y miembros.
 * La creación se hace desde GestorApps (selector «Grupo global»).
 */
class GruposGlobales extends Component
{
    use RequiresAdmin;

    public ?string $grupoAbierto = null;   // cn del grupo cuyo modal de miembros está abierto
    public string  $uidNuevo     = '';
    public string  $error        = '';
    public string  $flash        = '';

    protected LdapService $ldap;

    public function boot(LdapService $ldap)
    {
        $this->ldap = $ldap;
    }

    /** Refresca el listado cuando GestorApps crea un grupo global. */
    #[On('grupo-global-actualizado')]
    public function refrescar(): void
    {
    }

    public function gestionar(string $cn): void
    {
        $this->authorizeAdmin();
        $this->reset(['uidNuevo', 'error', 'flash']);

        $group = $this->ldap->getGroup($cn);
        $this->grupoAbierto = ($group && $group['global']) ? $group['cn'] : null;
    }

    public function cerrar(): void
    {
        $this->reset(['grupoAbierto', 'uidNuevo', 'error']);
    }

    public function anadirMiembro(): void
    {
        $this->authorizeAdmin();
        $this->error = '';

        if (!$this->grupoAbierto) return;
        if ($this->uidNuevo === '') {
            $this->error = 'Elige un usuario.';
            return;
        }

        try {
            $ok = $this->ldap->addGlobalGroupMember($this->grupoAbierto, $this->uidNuevo);
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            return;
        }

        if ($ok) {
            $this->flash   = "'{$this->uidNuevo}' añadido a '{$this->grupoAbierto}'.";
            $this->uidNuevo = '';
        } else {
            $this->error = 'No se pudo añadir el miembro en LDAP. Revisa los logs.';
        }
    }

    public function quitarMiembro(string $uid): void
    {
        $this->authorizeAdmin();
        $this->error = '';

        if (!$this->grupoAbierto) return;

        try {
            $ok = $this->ldap->removeGlobalGroupMember($this->grupoAbierto, $uid);
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            return;
        }

        if ($ok) {
            $this->flash = "'{$uid}' quitado de '{$this->grupoAbierto}'.";
        } else {
            $this->error = 'No se pudo quitar el miembro en LDAP. Revisa los logs.';
        }
    }

    public function render()
    {
        $grupos = $this->ldap->getGlobalGroups();

        $abierto  = null;
        $miembros = [];
        $candidatos = [];

        if ($this->grupoAbierto) {
            $abierto = collect($grupos)->first(fn($g) => strcasecmp($g['cn'], $this->grupoAbierto) === 0);
            if ($abierto) {
                $usuarios = collect($this->ldap->getAllUsers())->keyBy('uid');
                // Miembros con nombre legible; si el uid ya no existe en el directorio se muestra tal cual
                $miembros = collect($abierto['miembros'])->map(fn($uid) => [
                    'uid'    => $uid,
                    'nombre' => isset($usuarios[$uid])
                        ? trim($usuarios[$uid]['apellidos'] . ', ' . $usuarios[$uid]['nombre'], ', ')
                        : $uid,
                ])->all();
                $candidatos = $usuarios->reject(fn($u) => in_array(strtolower($u['uid']),
                    array_map('strtolower', $abierto['miembros']), true))->values()->all();
            } else {
                $this->grupoAbierto = null;
            }
        }

        return view('livewire.aplicaciones.grupos-globales', [
            'grupos'     => $grupos,
            'abierto'    => $abierto,
            'miembros'   => $miembros,
            'candidatos' => $candidatos,
        ]);
    }
}
