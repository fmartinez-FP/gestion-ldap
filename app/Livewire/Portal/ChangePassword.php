<?php
namespace App\Livewire\Portal;
use App\Services\LdapService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ChangePassword extends Component
{
    public string  $currentPassword         = '';
    public string  $newPassword             = '';
    public string  $newPasswordConfirmation = '';
    public ?string $successMessage          = null;
    public ?string $errorMessage            = null;

    protected function rules(): array
    {
        return [
            'currentPassword'         => ['required', 'string'],
            'newPassword'             => [
                'required', 'string', 'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[#@$!%*?\-_+=&^~]/',
            ],
            'newPasswordConfirmation' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'currentPassword.required'         => 'La contraseña actual es obligatoria.',
            'newPassword.required'             => 'La nueva contraseña es obligatoria.',
            'newPassword.min'                  => 'Mínimo 8 caracteres.',
            'newPassword.regex'                => 'La contraseña no cumple todos los requisitos.',
            'newPasswordConfirmation.required' => 'Confirma la nueva contraseña.',
        ];
    }

    public function submitChangePassword(): void
    {
        $this->successMessage = null;
        $this->errorMessage   = null;
        $this->validate();

        if ($this->newPassword !== $this->newPasswordConfirmation) {
            $this->addError('newPasswordConfirmation', 'Las contraseñas no coinciden.');
            return;
        }

        $user = session('ldap_user');
        if (!$user) {
            $this->errorMessage = 'Sesión expirada. Vuelve a iniciar sesión.';
            return;
        }

        try {
            $ldap = new LdapService();

            if (session('ldap_must_change_password')) {
                // Cambio FORZADO: OpenLDAP bloquea el bind cuando pwdReset:TRUE,
                // el admin cambia la contraseña y borra pwdReset explícitamente.
                if ($ldap->setPassword($user['uid'], $this->newPassword)) {
                    $ldap->clearPwdReset($user['uid']);
                    $this->redirect(route('portal.password.done'));
                    return;
                }
                $this->errorMessage = 'Error al cambiar la contraseña. Inténtalo de nuevo.';
                return;
            }

            // Cambio VOLUNTARIO: el usuario no tiene pwdReset:TRUE, bind funciona.
            if (LdapService::changeUserOwnPassword($user['uid'], $this->currentPassword, $this->newPassword)) {
                $this->successMessage          = '¡Contraseña cambiada correctamente!';
                $this->currentPassword         = '';
                $this->newPassword             = '';
                $this->newPasswordConfirmation = '';
            } else {
                $this->errorMessage = 'La contraseña actual no es correcta.';
            }

        } catch (\RuntimeException $e) {
            Log::error('Portal changePassword: ' . $e->getMessage());
            $this->errorMessage = 'Error de conexión LDAP. Inténtalo más tarde.';
        }
    }

    public function render()
    {
        return view('livewire.portal.change-password');
    }
}
