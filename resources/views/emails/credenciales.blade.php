<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Credenciales de acceso — {{ config('app.center_name') }}</title>
<style>
  body { margin:0; padding:0; background:#f8fafc; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrap { max-width:560px; margin:32px auto; background:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0; }
  .header { background:linear-gradient(135deg,#0f172a,#1e293b); padding:32px; text-align:center; }
  .header h1 { color:#10B981; margin:0; font-size:22px; font-weight:800; letter-spacing:-0.5px; }
  .header p { color:#94a3b8; margin:6px 0 0; font-size:13px; }
  .body { padding:32px; }
  .greeting { color:#1e293b; font-size:16px; font-weight:600; margin-bottom:8px; }
  .text { color:#64748b; font-size:14px; line-height:1.6; margin-bottom:24px; }
  .cred-box { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:24px; }
  .cred-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #e2e8f0; }
  .cred-row:last-child { border-bottom:none; }
  .cred-label { color:#64748b; font-size:13px; }
  .cred-value { font-family:monospace; font-weight:700; color:#0f172a; font-size:14px; background:#fff; padding:3px 10px; border-radius:6px; border:1px solid #e2e8f0; }
  .apps { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px; }
  .app-badge { background:#10b98115; color:#059669; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
  .warning { background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px 16px; color:#92400e; font-size:12px; margin-bottom:24px; }
  .footer { background:#f8fafc; padding:20px 32px; text-align:center; color:#94a3b8; font-size:12px; border-top:1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>{{ config('app.center_name') }}</h1>
    <p>Sistema centralizado de gestión</p>
  </div>
  <div class="body">
    <p class="greeting">Hola, {{ $nombre }}.</p>
    <p class="text">
      Se ha creado tu cuenta de acceso en el sistema de gestión del {{ config('app.center_name') }}.
      A continuación encontrarás tus credenciales de acceso:
    </p>

    <div class="cred-box">
      <div class="cred-row">
        <span class="cred-label">Usuario</span>
        <span class="cred-value">{{ $username }}</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">Contraseña temporal</span>
        <span class="cred-value">{{ $password }}</span>
      </div>
    </div>

    @if(!empty($apps))
    <p class="text" style="margin-bottom:8px;">Tienes acceso a las siguientes aplicaciones:</p>
    <div class="apps">
      @foreach($apps as $app)
      <span class="app-badge">{{ $app }}</span>
      @endforeach
    </div>
    @endif

    <div class="warning">
      <strong>⚠️ Importante:</strong> Esta es una contraseña temporal. Por seguridad, cámbiala en tu primer acceso al sistema.
    </div>

    <p class="text">
      Si tienes algún problema para acceder, contacta con el administrador del sistema.
    </p>
  </div>
  <div class="footer">
    {{ config('app.center_name') }} · Madrid · Correo generado automáticamente, no responder a este mensaje.
  </div>
</div>
</body>
</html>
