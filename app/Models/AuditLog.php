<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps  = false;
    protected $fillable = ['admin_username', 'action', 'target_uid', 'details', 'ip_address'];
    protected $casts    = ['details' => 'array'];

    /**
     * Registra una acción de administrador.
     * Obtiene automáticamente el usuario y la IP del contexto actual.
     */
    public static function record(string $action, ?string $targetUid = null, array $details = []): void
    {
        try {
            $admin = Auth::guard('admin')->user();
            static::create([
                'admin_username' => $admin?->username ?? 'system',
                'action'         => $action,
                'target_uid'     => $targetUid,
                'details'        => empty($details) ? null : $details,
                'ip_address'     => Request::ip(),
            ]);
        } catch (\Throwable) {
            // El fallo de auditoría nunca debe interrumpir la operación principal
        }
    }
}
