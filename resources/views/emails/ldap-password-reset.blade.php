<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f1f5f9">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:40px 20px">
  <tr><td align="center">
    <table role="presentation" width="580" cellspacing="0" cellpadding="0"
           style="background:#ffffff;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.07);overflow:hidden">

      {{-- Header --}}
      <tr>
        <td style="background:linear-gradient(135deg,#0d1b2a 0%,#162436 100%);padding:36px 40px;text-align:center">
          <h1 style="color:#ffffff;margin:0 0 4px 0;font-size:22px;font-weight:700">{{ config('app.center_name') }}</h1>
          <p style="color:#f72585;margin:0;font-size:13px;font-weight:600;letter-spacing:.5px">
            PORTAL DE GESTIÓN DE PROFESORES
          </p>
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:40px">
          <h2 style="color:#0d1b2a;margin:0 0 16px 0;font-size:20px;font-weight:700">
            Restablecer contraseña
          </h2>
          <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 16px 0">
            Hola, <strong>{{ $nombreCompleto }}</strong>:
          </p>
          <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px 0">
            Has solicitado restablecer la contraseña de tu cuenta en los
            <strong>sistemas internos del {{ config('app.center_name') }}</strong>.
            Haz clic en el botón para crear una nueva:
          </p>

          {{-- CTA --}}
          <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 28px 0">
            <tr>
              <td style="background:linear-gradient(135deg,#f72585,#c91e6d);border-radius:12px">
                <a href="{{ $resetUrl }}" target="_blank"
                   style="display:inline-block;padding:15px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700">
                  Restablecer contraseña
                </a>
              </td>
            </tr>
          </table>

          <p style="color:#64748b;font-size:13px;line-height:1.6;margin:0 0 12px 0">
            Este enlace <strong>caduca en 60 minutos</strong>.
          </p>
          <p style="color:#64748b;font-size:13px;line-height:1.6;margin:0 0 24px 0">
            Si no has solicitado este cambio, ignora este mensaje. Tu contraseña actual seguirá siendo válida.
          </p>

          {{-- Aviso EducaMadrid --}}
          <div style="background:#fef3f9;border:1px solid #fbbddd;border-radius:10px;padding:14px 18px;margin-bottom:24px">
            <p style="color:#9d174d;font-size:13px;line-height:1.6;margin:0;font-weight:600">
              ⚠️ Esta contraseña NO es tu contraseña de EducaMadrid
            </p>
            <p style="color:#9d174d;font-size:13px;line-height:1.6;margin:6px 0 0 0">
              Solo afecta a los sistemas internos del centro. Se recomienda que ambas contraseñas sean diferentes.
            </p>
          </div>

          <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 20px 0">
          <p style="color:#94a3b8;font-size:12px;line-height:1.6;margin:0">
            Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
            <a href="{{ $resetUrl }}" style="color:#f72585;word-break:break-all">{{ $resetUrl }}</a>
          </p>
        </td>
      </tr>

      {{-- Footer --}}
      <tr>
        <td style="background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0">
          <p style="color:#94a3b8;font-size:12px;margin:0">
            © {{ date('Y') }} {{ config('app.center_name') }} · Este es un correo automático, no respondas a este mensaje.
          </p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
