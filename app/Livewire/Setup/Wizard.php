<?php

namespace App\Livewire\Setup;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Wizard extends Component
{
    use WithFileUploads;

    public int $step = 1;
    public int $totalSteps = 4;

    // ── Paso 1: Información del centro ────────────────────────────────────────
    #[Rule('required|string|min:3|max:100')]
    public string $centerName = '';

    public $logo = null;

    // ── Paso 2: Contraseña del administrador ──────────────────────────────────
    #[Rule('required|string|min:8')]
    public string $adminPassword = '';

    #[Rule('required|same:adminPassword')]
    public string $adminPasswordConfirm = '';

    // ── Paso 3: Test de correo electrónico ────────────────────────────────────
    public string $testEmail = '';
    public ?string $smtpStatus = null;
    public bool $smtpSkipped = false;

    // ── Estado general ────────────────────────────────────────────────────────
    public bool $completed = false;

    public function mount(): void
    {
        // Si ya está configurado, redirigir al dashboard
        if (Setting::isConfigured()) {
            $this->redirect(route('dashboard'));
        }
    }

    // ── Paso 1: Guardar nombre e imagen del centro ────────────────────────────

    public function saveCenterInfo(): void
    {
        $this->validateOnly('centerName');

        if ($this->logo) {
            $this->validate(['logo' => 'image|max:2048|mimes:png,jpg,jpeg,svg,webp']);

            // Guardar como logo.png en public/images/
            $path = $this->logo->storeAs('', 'logo.png', 'public_images');
        }

        Setting::set('center_name', $this->centerName);

        $this->step = 2;
    }

    // ── Paso 2: Cambiar contraseña del administrador ──────────────────────────

    public function saveAdminPassword(): void
    {
        $this->validate([
            'adminPassword'        => 'required|string|min:8',
            'adminPasswordConfirm' => 'required|same:adminPassword',
        ]);

        $admin = Admin::first();
        if ($admin) {
            $admin->update(['password' => Hash::make($this->adminPassword)]);
        }

        $this->step = 3;
    }

    // ── Paso 3: Test SMTP ─────────────────────────────────────────────────────

    public function testSmtp(): void
    {
        $this->validate([
            'testEmail' => 'required|email',
        ]);

        $this->smtpStatus = null;

        try {
            Mail::raw(
                'Este es un correo de prueba del Gestor LDAP de ' . Setting::centerName() . '. Si lo recibes, la configuración SMTP es correcta.',
                fn ($m) => $m->to($this->testEmail)
                             ->subject('[Gestor LDAP] Test de correo electrónico')
            );
            $this->smtpStatus = 'ok';
        } catch (\Throwable $e) {
            $this->smtpStatus = 'error: ' . $e->getMessage();
        }
    }

    public function skipSmtp(): void
    {
        $this->smtpSkipped = true;
        $this->step = 4;
    }

    public function continueAfterSmtp(): void
    {
        $this->step = 4;
    }

    // ── Paso 4: Finalizar configuración ──────────────────────────────────────

    public function complete(): void
    {
        Setting::set('center_configured', '1');
        $this->completed = true;

        // Limpiar cache de config para que center_name se aplique en toda la app
        \Artisan::call('config:cache');

        $this->redirect(route('dashboard'));
    }

    // ── Navegación ────────────────────────────────────────────────────────────

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        return view('livewire.setup.wizard', [
            'stepLabels' => [
                1 => 'Centro',
                2 => 'Administrador',
                3 => 'Correo',
                4 => 'Completado',
            ],
        ]);
    }
}
