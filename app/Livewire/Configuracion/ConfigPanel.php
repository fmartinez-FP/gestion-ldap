<?php

namespace App\Livewire\Configuracion;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ConfigPanel extends Component
{
    // ── SMTP (editables) ───────────────────────────────────────────────
    public string $smtpHost       = '';
    public string $smtpPort       = '587';
    public string $smtpUser       = '';
    public string $smtpPassword   = '';
    public string $smtpEncryption = 'tls';
    public string $smtpFrom       = '';
    public string $smtpFromName   = '';

    // ── SMTP feedback ──────────────────────────────────────────────────
    public ?string $flashSmtp = null;
    public ?string $errorSmtp = null;

    // ── SMTP test ──────────────────────────────────────────────────────
    public string  $testEmail = '';
    public ?string $flashMail = null;
    public ?string $errorMail = null;

    // ── LDAP info (solo lectura) ───────────────────────────────────────
    public string $ldapHost = '';
    public string $ldapBase = '';

    public function mount(): void
    {
        $this->smtpHost       = config('mail.mailers.smtp.host', '');
        $this->smtpPort       = (string) config('mail.mailers.smtp.port', '587');
        $this->smtpUser       = config('mail.mailers.smtp.username', '');
        $this->smtpPassword   = config('mail.mailers.smtp.password', '');
        $this->smtpEncryption = config('mail.mailers.smtp.encryption', 'tls');
        $this->smtpFrom       = config('mail.from.address', '');
        $this->smtpFromName   = config('mail.from.name', '');
        $this->ldapHost       = config('ldap.connections.default.hosts.0', '');
        $this->ldapBase       = config('ldap.connections.default.base_dn', '');
    }

    public function guardarSmtp(): void
    {
        $this->flashSmtp = null;
        $this->errorSmtp = null;

        $this->validate([
            'smtpHost' => ['required', 'string'],
            'smtpPort' => ['required', 'numeric', 'between:1,65535'],
            'smtpFrom' => ['required', 'email'],
        ], [
            'smtpHost.required' => 'El servidor SMTP es obligatorio.',
            'smtpPort.required' => 'El puerto es obligatorio.',
            'smtpPort.numeric'  => 'El puerto debe ser numérico.',
            'smtpFrom.email'    => 'El remitente debe ser un email válido.',
        ]);

        try {
            $this->writeEnv([
                'MAIL_HOST'         => $this->smtpHost,
                'MAIL_PORT'         => $this->smtpPort,
                'MAIL_USERNAME'     => $this->smtpUser,
                'MAIL_PASSWORD'     => $this->smtpPassword,
                'MAIL_ENCRYPTION'   => $this->smtpEncryption,
                'MAIL_FROM_ADDRESS' => '"' . $this->smtpFrom . '"',
                'MAIL_FROM_NAME'    => '"' . $this->smtpFromName . '"',
            ]);

            Artisan::call('config:clear');
            $this->flashSmtp = 'Configuración SMTP guardada correctamente.';
        } catch (\Throwable $e) {
            Log::error('ConfigPanel guardarSmtp: ' . $e->getMessage());
            $this->errorSmtp = 'Error al guardar: ' . $e->getMessage();
        }
    }

    public function testSmtp(): void
    {
        $this->flashMail = null;
        $this->errorMail = null;

        if (empty(trim($this->testEmail))) {
            $this->errorMail = 'Introduce una dirección de correo de prueba.';
            return;
        }

        try {
            Mail::raw('Correo de prueba desde el panel LDAP de IES Pacífico.', function ($m) {
                $m->to($this->testEmail)->subject('Prueba SMTP — IES Pacífico');
            });
            $this->flashMail = "Correo enviado a {$this->testEmail} correctamente.";
        } catch (\Throwable $e) {
            $this->errorMail = 'Error al enviar: ' . $e->getMessage();
        }
    }

    // Actualiza claves en el fichero .env mediante sustitución regex línea a línea.
    private function writeEnv(array $data): void
    {
        $path    = base_path('.env');
        $content = file_get_contents($path);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($path, $content);
    }

    public function render()
    {
        return view('livewire.configuracion.panel');
    }
}
