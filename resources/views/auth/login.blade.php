<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="/favicon.png">
<title>Gestión {{ config('app.center_name') }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  * { font-family: 'Plus Jakarta Sans', sans-serif; }
  body {
    background: linear-gradient(145deg, #f8fafc 0%, #f0f4f8 50%, #e8eef5 100%);
    min-height: 100vh;
  }
  /* Patrón sutil en el fondo */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      radial-gradient(circle at 25% 25%, rgba(247,37,133,0.04) 0%, transparent 50%),
      radial-gradient(circle at 75% 75%, rgba(13,27,42,0.04) 0%, transparent 50%);
    pointer-events: none;
  }
  .card {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.07);
    box-shadow: 0 20px 60px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.04);
  }
  .input-field {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    color: #0f172a;
    transition: all 0.2s;
  }
  .input-field:focus {
    background: #fff;
    border-color: #f72585;
    box-shadow: 0 0 0 3px rgba(247,37,133,0.1);
    outline: none;
  }
  .input-field::placeholder { color: #94a3b8; }
  .eye-btn { color: #94a3b8; transition: color .2s; }
  .eye-btn:hover { color: #475569; }
  .btn-login {
    background: linear-gradient(135deg, #f72585, #c91e6d);
    box-shadow: 0 4px 20px rgba(247,37,133,0.3);
    transition: all 0.2s;
  }
  .btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 28px rgba(247,37,133,0.4);
  }
  .btn-login:active { transform: translateY(0); }
</style>
</head>
<body class="flex items-center justify-center p-4">

<div class="w-full max-w-sm relative z-10">

  {{-- Logo {{ config('app.center_name') }} (grande, directo sobre fondo claro) --}}
  <div class="flex justify-center mb-5">
    <img src="/images/logo.png"
         class="object-contain"
         style="height: 100px;"
         alt="{{ config('app.center_name') }}">
  </div>

  {{-- Favicon + título --}}
  <div class="flex flex-col items-center gap-3 mb-8">
    <div class="relative">
      <img src="/favicon.png"
           class="rounded-2xl object-cover"
           style="width: 86px; height: 86px; box-shadow: 0 4px 20px rgba(0,0,0,0.12), 0 0 0 3px rgba(247,37,133,0.25);"
           alt="Gestión IES Pacífico">
      <span class="absolute -bottom-1.5 -right-1.5 w-5 h-5 rounded-full border-2 border-white
                   flex items-center justify-center"
            style="background: #f72585; box-shadow: 0 2px 6px rgba(247,37,133,0.4)">
        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd"/>
        </svg>
      </span>
    </div>
    <div class="text-center">
      <h1 class="text-slate-900 font-extrabold text-xl leading-tight">Portal de gestión</h1>
      <p class="text-slate-500 text-sm font-medium">de profesores · {{ config('app.center_name') }}</p>
    </div>
  </div>

  {{-- Tarjeta de login --}}
  <div class="card rounded-2xl p-7">

    @if(session('status'))
      <div class="rounded-xl px-4 py-3 mb-5 border text-sm font-semibold"
           style="background: rgba(247,37,133,0.06); border-color: rgba(247,37,133,0.25); color: #c91e6d">
        {{ session('status') }}
      </div>
    @endif

    @if($errors->any())
      <div class="bg-red-50 border border-red-200 rounded-xl p-3.5 mb-5 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-red-700 text-sm font-medium">{{ $errors->first() }}</p>
      </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
      @csrf

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Usuario</label>
        <input type="text" name="username" value="{{ old('username') }}" required autofocus
               placeholder="Introduce tu usuario"
               class="input-field w-full px-4 py-2.5 rounded-xl text-sm">
      </div>

      <div x-data="{ show: false }">
        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña</label>
        <div class="relative">
          <input :type="show ? 'text' : 'password'" name="password" required
                 placeholder="••••••••"
                 class="input-field w-full px-4 py-2.5 pr-11 rounded-xl text-sm">
          <button type="button" @click="show = !show" class="eye-btn absolute right-3 top-1/2 -translate-y-1/2">
            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="flex items-center justify-between pt-1">
        <div class="flex items-center gap-2">
          <input type="checkbox" name="remember" id="remember"
                 class="w-4 h-4 rounded border-slate-300" style="accent-color: #f72585">
          <label for="remember" class="text-sm text-slate-500">Mantener sesión</label>
        </div>
        <a href="{{ route('ldap.password.request') }}"
           class="text-xs font-semibold" style="color: #f72585">
          ¿Olvidaste tu contraseña?
        </a>
      </div>

      <button type="submit" class="btn-login w-full py-2.5 rounded-xl text-white font-bold text-sm mt-2">
        Iniciar sesión
      </button>
    </form>
  </div>

  <p class="text-center text-slate-400 text-xs mt-6">
    Portal de gestión de profesores · {{ config('app.center_name') }} Madrid
  </p>
</div>

</body>
</html>
