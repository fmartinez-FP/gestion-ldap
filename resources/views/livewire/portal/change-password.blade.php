<div class="flex-1 p-8"
     x-data="{
         pw: '',
         showC:  false,
         showN:  false,
         showCf: false,
         allOk() {
             return this.pw.length >= 8
                 && /[a-z]/.test(this.pw)
                 && /[A-Z]/.test(this.pw)
                 && /[0-9]/.test(this.pw)
                 && /[#@$!%*?\-_+=&^~]/.test(this.pw);
         }
     }">
    <div class="max-w-lg">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Cambiar Contraseña</h1>
            <p class="text-slate-500 text-sm mt-1">Actualiza la contraseña de tu cuenta LDAP del {{ config('app.center_name') }}.</p>
        </div>

        {{-- Aviso EducaMadrid --}}
        <div class="rounded-2xl border p-5 mb-6"
             style="background: rgba(247,37,133,0.05); border-color: rgba(247,37,133,0.25)">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                     style="background: rgba(247,37,133,0.15)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #f72585">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-sm mb-1" style="color: #c91e6d">Esta contraseña NO es la misma que la de EducaMadrid</p>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Esta es tu contraseña de los <strong>sistemas internos del {{ config('app.center_name') }}</strong>.
                        No existe ninguna vinculación con tu cuenta de <strong>EducaMadrid</strong> más allá del nombre de usuario.<br>
                        <span class="text-slate-500 text-xs mt-1 block">Por seguridad, se recomienda que ambas contraseñas sean <em>diferentes</em>.</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-7">

            @if($successMessage)
                <div class="flex items-start gap-3 rounded-xl px-4 py-3.5 mb-6 border"
                     style="background: rgba(247,37,133,0.06); border-color: rgba(247,37,133,0.25)">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #f72585">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-semibold" style="color: #c91e6d">{{ $successMessage }}</p>
                </div>
            @endif
            @if($errorMessage)
                <div class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3.5 mb-6">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-red-700 text-sm font-semibold">{{ $errorMessage }}</p>
                </div>
            @endif

            <form wire:submit="submitChangePassword" class="space-y-5">

                {{-- Contraseña actual --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña actual</label>
                    <div class="relative">
                        <input :type="showC ? 'text' : 'password'"
                               wire:model="currentPassword" autocomplete="current-password"
                               placeholder="Tu contraseña actual del sistema"
                               class="w-full px-4 py-2.5 pr-11 rounded-xl border text-slate-900 text-sm transition-colors focus:outline-none
                                      {{ $errors->has('currentPassword') ? 'border-red-400 bg-red-50/50' : 'border-slate-300' }}">
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
                    @error('currentPassword') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- Nueva contraseña --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nueva contraseña</label>
                    <div class="relative">
                        <input :type="showN ? 'text' : 'password'"
                               wire:model="newPassword"
                               @input="pw = $event.target.value"
                               autocomplete="new-password"
                               placeholder="Elige una contraseña segura"
                               class="w-full px-4 py-2.5 pr-11 rounded-xl border text-slate-900 text-sm transition-colors focus:outline-none
                                      {{ $errors->has('newPassword') ? 'border-red-400 bg-red-50/50' : 'border-slate-300' }}">
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
                    <div class="mt-3 bg-slate-50 rounded-xl border border-slate-200 p-3.5 space-y-2">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Requisitos</p>
                        <div class="flex items-center gap-2">
                            <svg x-show="pw.length >= 8" x-cloak class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="pw.length < 8" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-xs" :class="pw.length >= 8 ? 'text-slate-700 font-medium' : 'text-red-400'">Al menos 8 caracteres</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg x-show="/[a-z]/.test(pw)" x-cloak class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!/[a-z]/.test(pw)" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-xs" :class="/[a-z]/.test(pw) ? 'text-slate-700 font-medium' : 'text-red-400'">Al menos una minúscula (a–z)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg x-show="/[A-Z]/.test(pw)" x-cloak class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!/[A-Z]/.test(pw)" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-xs" :class="/[A-Z]/.test(pw) ? 'text-slate-700 font-medium' : 'text-red-400'">Al menos una mayúscula (A–Z)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg x-show="/[0-9]/.test(pw)" x-cloak class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!/[0-9]/.test(pw)" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-xs" :class="/[0-9]/.test(pw) ? 'text-slate-700 font-medium' : 'text-red-400'">Al menos un número (0–9)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg x-show="/[#@$!%*?\-_+=&^~]/.test(pw)" x-cloak class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f72585"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!/[#@$!%*?\-_+=&^~]/.test(pw)" class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-xs" :class="/[#@$!%*?\-_+=&^~]/.test(pw) ? 'text-slate-700 font-medium' : 'text-red-400'">Al menos un carácter especial (#, @, $…)</span>
                        </div>
                    </div>
                </div>

                {{-- Confirmar --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmar nueva contraseña</label>
                    <div class="relative">
                        <input :type="showCf ? 'text' : 'password'"
                               wire:model="newPasswordConfirmation" autocomplete="new-password"
                               placeholder="Repite la nueva contraseña"
                               class="w-full px-4 py-2.5 pr-11 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none">
                        <button type="button" @click="showCf = !showCf"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                            <svg x-show="!showCf" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showCf" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled" :disabled="!allOk()"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-white
                               text-sm font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background: #f72585; box-shadow: 0 4px 16px rgba(247,37,133,0.3)">
                    <span wire:loading.remove wire:target="submitChangePassword">Cambiar contraseña</span>
                    <span wire:loading wire:target="submitChangePassword" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Procesando…
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>
