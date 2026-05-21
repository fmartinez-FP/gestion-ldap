<?php
namespace App\Livewire\Usuarios;
use App\Services\LdapService;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportarCSV extends Component
{
    use WithFileUploads;

    public mixed $archivo    = null;
    public array $filas      = [];
    public array $resultado  = [];
    public int   $paso       = 1;   // 1=subir 2=previsualizar 3=resultados
    public array $gruposValidos = [];

    public function mount(): void
    {
        $ldap = new LdapService();
        $this->gruposValidos = array_column($ldap->getAllGroups(), 'cn');
    }

    protected function rules(): array
    {
        return ['archivo' => 'required|file|mimes:csv,txt|max:4096'];
    }

    public function cargarPrevia(): void
    {
        $this->validate();
        $this->filas = [];

        $handle = fopen($this->archivo->getRealPath(), 'r');
        fgetcsv($handle); // saltar cabecera

        $n = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count(array_filter($row)) === 0) continue;
            $n++;
            $nombre    = trim($row[0] ?? '');
            $apellidos = trim($row[1] ?? '');
            $email     = trim($row[2] ?? '');
            $grupos    = array_filter(array_map('trim', explode(';', $row[3] ?? '')));
            $uid       = strtolower(strstr($email, '@', true) ?: '');

            $errores = [];
            if (!$nombre)    $errores[] = 'Nombre vacío';
            if (!$apellidos) $errores[] = 'Apellidos vacíos';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido';
            if (!$uid)       $errores[] = 'No se puede derivar UID del email';
            foreach ($grupos as $g) {
                if (!in_array($g, $this->gruposValidos))
                    $errores[] = "Grupo '$g' no existe";
            }

            $this->filas[] = compact('n', 'nombre', 'apellidos', 'email', 'uid', 'grupos', 'errores');
        }
        fclose($handle);
        $this->paso = 2;
    }

    public function importar(): void
    {
        $this->resultado = [];
        $ldap = new LdapService();

        foreach ($this->filas as $f) {
            if (!empty($f['errores'])) {
                $this->resultado[] = ['uid' => $f['uid'] ?: "fila {$f['n']}", 'ok' => false,
                    'msg' => 'Omitido: ' . implode(', ', $f['errores'])];
                continue;
            }
            try {
                $ok = $ldap->createUser([
                    'uid'             => $f['uid'],
                    'nombre'          => $f['nombre'],
                    'apellidos'       => $f['apellidos'],
                    'nombre_completo' => $f['nombre'] . ' ' . $f['apellidos'],
                    'mail'            => $f['email'],
                    'password'        => $f['uid'] . '1234',
                    'activo'          => true,
                    'grupos'          => $f['grupos'],
                ]);
                $this->resultado[] = ['uid' => $f['uid'], 'ok' => $ok,
                    'msg' => $ok ? 'Creado correctamente' : 'Error al crear (¿ya existe?)'];
            } catch (\Throwable $e) {
                $this->resultado[] = ['uid' => $f['uid'], 'ok' => false, 'msg' => $e->getMessage()];
            }
        }
        $this->paso = 3;
    }

    public function reiniciar(): void
    {
        $this->archivo = null; $this->filas = []; $this->resultado = []; $this->paso = 1;
    }

    public function render()
    {
        return view('livewire.usuarios.importar-csv', [
            'validos'   => count(array_filter($this->filas,   fn($f) => empty($f['errores']))),
            'invalidos' => count(array_filter($this->filas,   fn($f) => !empty($f['errores']))),
            'creados'   => count(array_filter($this->resultado, fn($r) => $r['ok'])),
            'fallidos'  => count(array_filter($this->resultado, fn($r) => !$r['ok'])),
        ]);
    }
}
