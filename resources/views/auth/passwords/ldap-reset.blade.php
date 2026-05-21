<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="/favicon.png">
<title>Gestión {{ config('app.center_name') }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
  * { font-family: 'Plus Jakarta Sans', sans-serif; }
  body { background: linear-gradient(135deg, #0d1b2a 0%, #0a1520 100%); }
  .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(12px); }
  .input-field { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: white; transition: all .2s; }
  .input-field:focus { border-color: #f72585; box-shadow: 0 0 0 3px rgba(247,37,133,0.15); outline: none; }
  .input-field::placeholder { color: rgba(255,255,255,0.25); }
  .btn { background: linear-gradient(135deg, #f72585, #c91e6d); box-shadow: 0 4px 20px rgba(247,37,133,0.35); transition: all .2s; }
  .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(247,37,133,0.45); }
  .btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm"
     x-data="{
         pw: '',
         showN: false,
         showC: false,
         allOk() {
             return this.pw.length >= 8
                 && /[a-z]/.test(this.pw)
                 && /[A-Z]/.test(this.pw)
                 && /[0-9]/.test(this.pw)
                 && /[#@$!%*?\-_+=&^~]/.test(this.pw);
         }
     }">

  <div class="text-center mb-8">
    <div class="flex justify-center mb-4">
      <div class="bg-white rounded-2xl px-6 py-3 inline-block" style="box-shadow: 0 8px 32px rgba(0,0,0,0.4)">
        <img src="/images/logo.png" class="h-12 object-contain" alt="{{ config('app.center_name') }}">
      </div>
    </div>
    <h1 class="text-white font-bold text-xl">Nueva contraseña</h1>
    <p class="text-xs mt-1" style="color: rgba(247,37,133,0.8)">Portal de gestión de profesores</p>
  </div>

  <div class="card rounded-2xl p-7">

    @if($errors->any())
      <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-3.5 mb-5">
        <p class="text-red-300 text-sm">{{ $errors->first() }}</p>
      </div>
    @endif

    <form method="POST" action="{{ route('ldap.password.update') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <input type="hidden" name="email" value="{{ $email }}">

      {{-- Nueva contraseña --}}
      <div>
        <label class="block text-sm font-semibold mb-1.5" style="color:rgba(255,255,255,0.7)">Nueva contraseña</label>
        <div class="relative">
          <input :type="showN ? 'text' : 'password'"
                 x-model="pw" name="password" required
                 placeholder="Elige una contraseña segura"
                 class="input-field w-full px-4 py-2.5 pr-11 rounded-xl text-sm">
          <button type="button" @click="showN = !showN"
                  class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors"
                  style="color: rgba(255,255,255,0.3)">
            <svg x-show="!showN" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="showN" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>

        {{-- Checklist --}}
        <div class="mt-2.5 rounded-xl p-3 space-y-1.5" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07)">
          <template x-for="[label, cond] in [
            ['Al menos 8 caracteres',              pw.length >= 8],
            ['Al menos una minúscula (a–z)',        /[a-z]/.test(pw)],
            ['Al menos una mayúscula (A–Z)',        /[A-Z]/.test(pw)],
            ['Al menos un número (0–9)',            /[0-9]/.test(pw)],
            ['Al menos un carácter especial (#,@…)',/[#@$!%*?\\-_+=&^~]/.test(pw)]
          ]" :key="label">
            <div class="flex items-center gap-2">
              <svg x-show="cond" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
              <svg x-show="!cond" class="w-3.5 h-3.5 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              <span class="text-xs" :class="cond ? 'text-slate-300' : 'text-red-400'" x-text="label"></span>
            </div>
          </template>
        </div>
      </div>

      {{-- Confirmar --}}
      <div>
        <label class="block text-sm font-semibold mb-1.5" style="color:rgba(255,255,255,0.7)">Confirmar contraseña</label>
        <div class="relative">
          <input :type="showC ? 'text' : 'password'"
                 name="password_confirmation" required
                 placeholder="Repite la contraseña"
                 class="input-field w-full px-4 py-2.5 pr-11 rounded-xl text-sm">
          <button type="button" @click="showC = !showC"
                  class="absolute right-3 top-1/2 -translate-y-1/2"
                  style="color: rgba(255,255,255,0.3)">
            <svg x-show="!showC" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="showC" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" :disabled="!allOk()" class="btn w-full py-2.5 rounded-xl text-white font-bold text-sm">
        Establecer nueva contraseña
      </button>
    </form>
  </div>

  <div class="text-center mt-5">
    <a href="{{ route('login') }}" class="text-sm" style="color: rgba(255,255,255,0.35)">← Volver al inicio de sesión</a>
  </div>
</div>
</body>
</html>
