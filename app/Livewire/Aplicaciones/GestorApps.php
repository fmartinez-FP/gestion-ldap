<?php

namespace App\Livewire\Aplicaciones;

use Livewire\Component;
use App\Livewire\Concerns\RequiresAdmin;
use App\Models\Aplicacion;
use App\Services\LdapService;

class GestorApps extends Component
{
    use RequiresAdmin;

    public bool   $mostrarModal = false;
    public ?int   $editandoId   = null;
    public string $flash        = '';

    // Tipo de grupo que se crea: 'aplicacion' (con fila en aplicaciones) o 'global' (solo grupo LDAP)
    public string $tipo           = 'aplicacion';
    public string $miembroInicial = '';

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
        $this->authorizeAdmin();

        $this->reset(['nombre','codigo','descripcion','url','activo','orden','editandoId','tipo','miembroInicial']);
        $this->resetValidation();
        $this->color       = '#3B82F6';
        $this->activo      = true;
        $this->mostrarModal = true;
    }

    public function editar(int $id): void
    {
        $this->authorizeAdmin();

        $app = Aplicacion::findOrFail($id);
        $this->tipo        = 'aplicacion';
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
        $this->authorizeAdmin();

        if (!$this->editandoId && $this->tipo === 'global') {
            $this->guardarGrupoGlobal();
            return;
        }

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
            // Un grupo global no puede reutilizarse como aplicación
            $existente = $this->ldap->getGroup($this->codigo);
            if ($existente && $existente['global']) {
                $this->addError('codigo', "'{$existente['cn']}' es un grupo global y no puede usarse como aplicación.");
                return;
            }

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

    /** Crea un grupo global: solo el grupo en LDAP, sin fila en aplicaciones, URL ni IP. */
    private function guardarGrupoGlobal(): void
    {
        $this->validate([
            'codigo'         => [
                'required',
                'max:64',
                'regex:/^[A-Za-z0-9_-]+\z/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $enLdap = $this->ldap->groupExists($value);
                    $enApps = Aplicacion::whereRaw('LOWER(codigo) = ?', [strtolower($value)])->exists();
                    if ($enLdap || $enApps) {
                        $fail("Ya existe un grupo o aplicación llamado '{$value}'.");
                    }
                },
            ],
            'descripcion'    => 'required|max:255',
            'miembroInicial' => 'required',
        ], [
            'codigo.required'      => 'El nombre del grupo es obligatorio.',
            'codigo.max'           => 'El nombre no puede superar los 64 caracteres.',
            'codigo.regex'         => 'El nombre solo puede contener letras, números, guion y guion bajo.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'miembroInicial.required' => 'Elige el miembro inicial: un grupo necesita al menos uno.',
        ]);

        try {
            $ok = $this->ldap->createGlobalGroup($this->codigo, $this->descripcion, $this->miembroInicial);
        } catch (\InvalidArgumentException $e) {
            $this->addError('codigo', $e->getMessage());
            return;
        }

        if (!$ok) {
            $this->addError('codigo', 'No se pudo crear el grupo en LDAP. Revisa los logs.');
            return;
        }

        $this->flash = "Grupo global '{$this->codigo}' creado con '{$this->miembroInicial}' como miembro inicial.";
        $this->mostrarModal = false;
        $this->dispatch('grupo-global-actualizado');
    }

    public function eliminar(int $id): void
    {
        $this->authorizeAdmin();

        $app = Aplicacion::findOrFail($id);
        // El grupo LDAP no se elimina para preservar histórico — se desactiva la app
        $app->update(['activo' => false]);
        $this->flash = "Aplicación '{$app->nombre}' desactivada. El grupo LDAP se mantiene.";
    }

    public function render()
    {
        $apps = Aplicacion::orderBy('orden')->get();

        // Solo se consulta el directorio cuando el modal de grupo global está abierto
        $usuarios = ($this->mostrarModal && $this->tipo === 'global' && !$this->editandoId)
            ? $this->ldap->getAllUsers()
            : [];

        return view('livewire.aplicaciones.gestor', ['apps' => $apps, 'usuarios' => $usuarios]);
    }
}
