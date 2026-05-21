<?php

namespace App\Livewire\Aplicaciones;

use Livewire\Component;
use App\Models\Aplicacion;
use App\Services\LdapService;

class GestorApps extends Component
{
    public bool   $mostrarModal = false;
    public ?int   $editandoId   = null;
    public string $flash        = '';

    // Formulario
    public string $nombre      = '';
    public string $codigo      = '';
    public string $descripcion = '';
    public string $url         = '';
    public string $color       = '#3B82F6';
    public bool   $activo      = true;
    public int    $orden       = 0;

    protected LdapService $ldap;

    public function boot(LdapService $ldap)
    {
        $this->ldap = $ldap;
    }

    protected function rules(): array
    {
        return [
            'nombre'      => 'required|min:2|max:100',
            'codigo'      => 'required|min:2|max:50|alpha_dash',
            'descripcion' => 'nullable|max:255',
            'url'         => 'nullable|url',
            'color'       => 'required',
            'activo'      => 'boolean',
            'orden'       => 'integer|min:0',
        ];
    }

    public function nueva(): void
    {
        $this->reset(['nombre','codigo','descripcion','url','activo','orden','editandoId']);
        $this->color       = '#3B82F6';
        $this->activo      = true;
        $this->mostrarModal = true;
    }

    public function editar(int $id): void
    {
        $app = Aplicacion::findOrFail($id);
        $this->editandoId  = $id;
        $this->nombre      = $app->nombre;
        $this->codigo      = $app->codigo;
        $this->descripcion = $app->descripcion ?? '';
        $this->url         = $app->url ?? '';
        $this->color       = $app->color ?? '#3B82F6';
        $this->activo      = $app->activo;
        $this->orden       = $app->orden;
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $this->validate();

        if ($this->editandoId) {
            $app = Aplicacion::findOrFail($this->editandoId);
            $app->update([
                'nombre'      => $this->nombre,
                'descripcion' => $this->descripcion,
                'url'         => $this->url,
                'color'       => $this->color,
                'activo'      => $this->activo,
                'orden'       => $this->orden,
            ]);
            $this->flash = "Aplicación '{$this->nombre}' actualizada.";
        } else {
            // Crear también el grupo LDAP
            $this->ldap->createGroup($this->codigo, $this->nombre . ': ' . $this->descripcion);

            Aplicacion::create([
                'nombre'      => $this->nombre,
                'codigo'      => $this->codigo,
                'descripcion' => $this->descripcion,
                'url'         => $this->url,
                'color'       => $this->color,
                'activo'      => $this->activo,
                'orden'       => $this->orden,
            ]);
            $this->flash = "Aplicación '{$this->nombre}' creada y grupo LDAP '{$this->codigo}' generado.";
        }

        $this->mostrarModal = false;
    }

    public function eliminar(int $id): void
    {
        $app = Aplicacion::findOrFail($id);
        // El grupo LDAP no se elimina para preservar histórico — se desactiva la app
        $app->update(['activo' => false]);
        $this->flash = "Aplicación '{$app->nombre}' desactivada. El grupo LDAP se mantiene.";
    }

    public function render()
    {
        $apps = Aplicacion::orderBy('orden')->get();
        return view('livewire.aplicaciones.gestor', ['apps' => $apps]);
    }
}
