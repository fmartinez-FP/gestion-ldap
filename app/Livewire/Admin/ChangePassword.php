<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ChangePassword extends Component
{
    public string  $currentPass = '';
    public string  $newPass     = '';
    public string  $newPassConf = '';
    public ?string $successMsg  = null;
    public ?string $errorMsg    = null;

    protected function rules(): array
    {
        return [
            'currentPass' => ['required'],
            'newPass'     => [
                'required', 'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[#@$!%*?\-_+=&^~]/',
            ],
            'newPassConf' => ['required'],
        ];
    }

    protected function messages(): array
    {
        return [
            'currentPass.required' => 'Introduce tu contraseña actual.',
            'newPass.required'     => 'La nueva contraseña es obligatoria.',
            'newPass.min'          => 'Mínimo 8 caracteres.',
            'newPass.regex'        => 'La contraseña no cumple todos los requisitos.',
            'newPassConf.required' => 'Confirma la nueva contraseña.',
        ];
    }

    public function submit(): void
    {
        $this->successMsg = null;
        $this->errorMsg   = null;

        $this->validate();

        // Comparación manual — confirmed busca newPass_confirmation, pero la propiedad es newPassConf
        if ($this->newPass !== $this->newPassConf) {
            $this->addError('newPassConf', 'Las contraseñas no coinciden.');
            return;
        }

        $admin = Auth::guard('admin')->user();

        if (!Hash::check($this->currentPass, $admin->password)) {
            $this->errorMsg = 'La contraseña actual no es correcta.';
            return;
        }

        $admin->password = Hash::make($this->newPass);
        $admin->save();

        $this->successMsg  = '¡Contraseña de administrador actualizada correctamente!';
        $this->currentPass = '';
        $this->newPass     = '';
        $this->newPassConf = '';
    }

    public function render()
    {
        return view('livewire.admin.change-password');
    }
}
