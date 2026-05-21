<div class="max-w-2xl">

  {{-- Éxito --}}
  @if($guardado)
  <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 mb-5 flex items-center gap-3">
    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>
    <div>
      <p class="text-emerald-800 font-semibold text-sm">
        {{ $modo === 'crear' ? 'Usuario creado correctamente' : 'Usuario actualizado correctamente' }}
      </p>
      @if($modo === 'crear')
        <p class="text-emerald-600 text-xs mt-0.5">Se ha enviado un correo con las credenciales a {{ $mail }}</p>
      @endif
    </div>
    <div class="ml-auto">
      <a href="{{ route('usuarios.index') }}" class="text-emerald-700 hover:text-emerald-800 text-sm font-semibold">← Ver usuarios</a>
    </div>
  </div>
  @endif

  {{-- Error --}}
  @if($error)
  <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm flex items-center gap-2">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ $error }}
  </div>
  @endif

  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 lg:p-8 space-y-6">

    <div class="flex items-center justify-between">
      <h2 class="text-slate-800 font-bold text-lg">
        {{ $modo === 'crear' ? 'Datos del nuevo usuario' : 'Editar usuario: ' . $uidOriginal }}
      </h2>
      <a href="{{ route('usuarios.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </a>
    </div>

    {{-- Nombre y apellidos --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
        <input wire:model.live="nombre" type="text" placeholder="Juan"
          class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error('nombre') border-red-300 bg-red-50 @enderror">
        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Apellidos <span class="text-red-500">*</span></label>
        <input wire:model.live="apellidos" type="text" placeholder="García López"
          class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error('apellidos') border-red-300 bg-red-50 @enderror">
        @error('apellidos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
      </div>
    </div>

    {{-- Email --}}
    <div>
      <label class="block text-sm font-semibold text-slate-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
      <input wire:model.live="mail" type="email"
        placeholder="juan.garcia@educa.madrid.org"
        {{ $modo === 'editar' ? 'disabled' : '' }}
        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500
          {{ $modo === 'editar' ? 'bg-slate-50 text-slate-400 cursor-not-allowed' : '' }}
          @error('mail') border-red-300 bg-red-50 @enderror">
      @error('mail') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
      <p class="text-slate-400 text-xs mt-1">Debe ser @educa.madrid.org</p>
    </div>

    {{-- Preview credenciales (solo crear) --}}
    @if($modo === 'crear' && $uid_preview)
    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-2">
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Se creará con estas credenciales</p>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <p class="text-xs text-slate-400">Usuario</p>
          <p class="text-sm font-bold text-slate-800 font-mono">{{ $uid_preview }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-400">Contraseña temporal</p>
          <p class="text-sm font-bold text-slate-800 font-mono">{{ $password_preview }}</p>
        </div>
      </div>
      <p class="text-xs text-amber-600">⚠️ El usuario deberá cambiar la contraseña en su primer acceso</p>
    </div>
    @endif

    {{-- ── Cambiar contraseña (solo en editar) ──────────────────────────── --}}
    @if($modo === 'editar')
    <div class="border border-slate-200 rounded-2xl overflow-hidden"
         x-data="{
             open: false,
             pw: '',
             showN: false,
             showC: false,
             checks: {
                 length:  () => pw.length >= 8,
                 lower:   () => /[a-z]/.test(pw),
                 upper:   () => /[A-Z]/.test(pw),
                 number:  () => /[0-9]/.test(pw),
                 special: () => /[#@$!%*?\-_+=&^~]/.test(pw),
             },
             allOk() { return Object.values(this.checks).every(fn => fn()); }
         }">

      {{-- Cabecera colapsable --}}
      <button type="button" @click="open = !open"
              class="w-full flex items-center justify-between px-5 py-4 text-left
                     hover:bg-slate-50 transition-colors">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
               style="background: rgba(247,37,133,0.1)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-800">Cambiar contraseña</p>
            <p class="text-xs text-slate-400">Dejar cerrado para no modificarla</p>
          </div>
        </div>
        <svg class="w-5 h-5 text-slate-400 transition-transform duration-200"
             :class="open ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      {{-- Panel expandible --}}
      <div x-show="open" x-collapse class="border-t border-slate-200 p-5 space-y-4">

        {{-- Nueva contraseña --}}
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nueva contraseña</label>
          <div class="relative">
            <input :type="showN ? 'text' : 'password'"
                   wire:model="newPassword"
                   @input="pw = $event.target.value"
                   autocomplete="new-password"
                   placeholder="Introduce la nueva contraseña"
                   class="w-full px-4 py-2.5 pr-11 border border-slate-200 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:border-transparent
                          @error('newPassword') border-red-400 bg-red-50/50 @enderror">
            <button type="button" @click="showN = !showN"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
              <svg x-show="!showN" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg x-show="showN" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
              </svg>
            </button>
          </div>
          @error('newPassword') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror

          {{-- Checklist reactivo --}}
          <div x-show="pw.length > 0" class="mt-2.5 bg-slate-50 rounded-xl border border-slate-200 p-3 space-y-1.5">
            <div class="flex items-center gap-2">
              <svg x-show="checks.length()" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <svg x-show="!checks.length()" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span class="text-xs" :class="checks.length() ? 'text-slate-700 font-medium' : 'text-red-400'">Al menos 8 caracteres</span>
            </div>
            <div class="flex items-center gap-2">
              <svg x-show="checks.lower()" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <svg x-show="!checks.lower()" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span class="text-xs" :class="checks.lower() ? 'text-slate-700 font-medium' : 'text-red-400'">Minúscula (a–z)</span>
            </div>
            <div class="flex items-center gap-2">
              <svg x-show="checks.upper()" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <svg x-show="!checks.upper()" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span class="text-xs" :class="checks.upper() ? 'text-slate-700 font-medium' : 'text-red-400'">Mayúscula (A–Z)</span>
            </div>
            <div class="flex items-center gap-2">
              <svg x-show="checks.number()" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <svg x-show="!checks.number()" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span class="text-xs" :class="checks.number() ? 'text-slate-700 font-medium' : 'text-red-400'">Número (0–9)</span>
            </div>
            <div class="flex items-center gap-2">
              <svg x-show="checks.special()" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <svg x-show="!checks.special()" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span class="text-xs" :class="checks.special() ? 'text-slate-700 font-medium' : 'text-red-400'">Carácter especial (#, @, $…)</span>
            </div>
          </div>
        </div>

        {{-- Confirmar --}}
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmar contraseña</label>
          <div class="relative">
            <input :type="showC ? 'text' : 'password'"
                   wire:model="newPasswordConfirmation"
                   autocomplete="new-password"
                   placeholder="Repite la nueva contraseña"
                   class="w-full px-4 py-2.5 pr-11 border border-slate-200 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:border-transparent
                          @error('newPasswordConfirmation') border-red-400 bg-red-50/50 @enderror">
            <button type="button" @click="showC = !showC"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
              <svg x-show="!showC" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg x-show="showC" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
              </svg>
            </button>
          </div>
          @error('newPasswordConfirmation') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        {{-- Aviso propagación --}}
        <div class="flex items-start gap-2.5 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
          <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-blue-700 text-xs leading-relaxed">
            El cambio se aplicará en <strong>todas las aplicaciones</strong> del sistema
            (FFE, Guardias, Inventarios) al guardar.
          </p>
        </div>
      </div>
    </div>
    @endif

    {{-- Aplicaciones --}}
    <div>
      <label class="block text-sm font-semibold text-slate-700 mb-2">Acceso a aplicaciones</label>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        @foreach($apps as $app)
        <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all
          {{ in_array($app->codigo, $gruposSeleccionados) ? 'border-2' : 'border border-slate-200 hover:border-slate-300' }}"
          style="{{ in_array($app->codigo, $gruposSeleccionados) ? 'border-color:' . $app->color . ';background-color:' . $app->color . '10;' : '' }}">
          <input type="checkbox" wire:model.live="gruposSeleccionados"
            value="{{ $app->codigo }}" class="accent-emerald-500">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-800 truncate">{{ $app->nombre }}</p>
            <p class="text-xs text-slate-400 truncate">{{ $app->descripcion }}</p>
          </div>
        </label>
        @endforeach
      </div>
    </div>

    {{-- Estado activo --}}
    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
      <div>
        <p class="text-sm font-semibold text-slate-700">Cuenta activa</p>
        <p class="text-xs text-slate-400 mt-0.5">Si se desactiva, el usuario no podrá iniciar sesión en ninguna app</p>
      </div>
      <button wire:click="$toggle('activo')" type="button"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200
          {{ $activo ? 'bg-emerald-500' : 'bg-slate-300' }}">
        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition duration-200
          {{ $activo ? 'translate-x-5' : 'translate-x-0' }}"></span>
      </button>
    </div>

    {{-- Botones --}}
    <div class="flex gap-3 pt-2">
      <button wire:click="guardar" wire:loading.attr="disabled"
        class="flex-1 sm:flex-none flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600
               disabled:opacity-60 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-colors">
        <span wire:loading wire:target="guardar">
          <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </span>
        <span wire:loading.remove wire:target="guardar">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </span>
        {{ $modo === 'crear' ? 'Crear usuario' : 'Guardar cambios' }}
      </button>
      <a href="{{ route('usuarios.index') }}"
        class="px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors">
        Cancelar
      </a>
    </div>
  </div>
</div>
