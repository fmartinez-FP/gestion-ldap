<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="/favicon.png">
<title>Gestión {{ config('app.center_name') }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  * { font-family: 'Plus Jakarta Sans', sans-serif; }
  body { background: linear-gradient(135deg, #0d1b2a 0%, #0a1520 100%); }
  .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(12px); }
  .input-field { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: white; transition: all .2s; }
  .input-field:focus { border-color: #f72585; box-shadow: 0 0 0 3px rgba(247,37,133,0.15); outline: none; }
  .input-field::placeholder { color: rgba(255,255,255,0.25); }
  .btn { background: linear-gradient(135deg, #f72585, #c91e6d); box-shadow: 0 4px 20px rgba(247,37,133,0.35); transition: all .2s; }
  .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(247,37,133,0.45); }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm">

  <div class="text-center mb-8">
    <div class="flex justify-center mb-4">
      <div class="bg-white rounded-2xl px-6 py-3 inline-block" style="box-shadow: 0 8px 32px rgba(0,0,0,0.4)">
        <img src="/images/logo.png" class="h-12 object-contain" alt="{{ config('app.center_name') }}">
      </div>
    </div>
    <h1 class="text-white font-bold text-xl">Recuperar contraseña</h1>
    <p class="text-xs mt-1" style="color: rgba(247,37,133,0.8)">Portal de gestión de profesores</p>
  </div>

  <div class="card rounded-2xl p-7">

    @if(session('status'))
      <div class="rounded-xl px-4 py-3.5 mb-5 border text-sm font-semibold"
           style="background: rgba(247,37,133,0.08); border-color: rgba(247,37,133,0.3); color: #f72585">
        {{ session('status') }}
      </div>
    @endif

    @if($errors->any())
      <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-3.5 mb-5">
        <p class="text-red-300 text-sm">{{ $errors->first() }}</p>
      </div>
    @endif

    <p class="text-sm mb-5" style="color: rgba(255,255,255,0.55); line-height:1.6">
      Introduce tu nombre de usuario LDAP. Si tiene correo registrado,
      recibirás un enlace para restablecer tu contraseña.
    </p>

    <form method="POST" action="{{ route('ldap.password.email') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-semibold mb-1.5" style="color:rgba(255,255,255,0.7)">Nombre de usuario</label>
        <input type="text" name="username" value="{{ old('username') }}" required autofocus
               placeholder="Tu usuario LDAP (ej: jperez)"
               class="input-field w-full px-4 py-2.5 rounded-xl text-sm">
      </div>
      <button type="submit" class="btn w-full py-2.5 rounded-xl text-white font-bold text-sm">
        Enviar enlace de recuperación
      </button>
    </form>
  </div>

  <div class="text-center mt-5">
    <a href="{{ route('login') }}" class="text-sm" style="color: rgba(255,255,255,0.35)">
      ← Volver al inicio de sesión
    </a>
  </div>
</div>
</body>
</html>
