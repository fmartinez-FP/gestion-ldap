<div class="w-full max-w-2xl mx-auto">

    {{-- ── Cabecera ────────────────────────────────────────────────────────── --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-500/20 border border-blue-400/30 mb-4">
            <svg class="w-8 h-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Configuración inicial</h1>
        <p class="text-slate-400 mt-1 text-sm">Gestor centralizado de usuarios LDAP</p>
    </div>

    {{-- ── Barra de progreso ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-8 px-2">
        @foreach ($stepLabels as $num => $label)
            <div class="flex flex-col items-center flex-1 {{ $loop->last ? '' : 'relative' }}">
                {{-- Línea conectora --}}
                @unless ($loop->last)
                    <div class="absolute top-4 left-1/2 w-full h-0.5 {{ $step > $num ? 'bg-blue-500' : 'bg-slate-700' }}" style="transform: translateX(50%)"></div>
                @endunless

                {{-- Círculo --}}
                <div @class([
                    'relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold border-2 transition-all duration-300',
                    'bg-blue-500 border-blue-500 text-white'  => $step > $num,
                    'bg-blue-600 border-blue-400 text-white ring-4 ring-blue-400/20' => $step === $num,
                    'bg-slate-800 border-slate-600 text-slate-500' => $step < $num,
                ])>
                    @if ($step > $num)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        {{ $num }}
                    @endif
                </div>

                {{-- Etiqueta --}}
                <span @class([
                    'mt-2 text-xs font-medium',
                    'text-blue-400' => $step === $num,
                    'text-slate-400' => $step !== $num,
                ])>{{ $label }}</span>
            </div>
        @endforeach
    </div>

    {{-- ── Tarjeta principal ───────────────────────────────────────────────── --}}
    <div class="bg-slate-800/60 backdrop-blur border border-slate-700/50 rounded-2xl p-8 shadow-2xl">

        {{-- ══ PASO 1: Información del centro ══════════════════════════════════ --}}
        @if ($step === 1)
            <div>
                <h2 class="text-xl font-semibold text-white mb-1">Información del centro</h2>
                <p class="text-slate-400 text-sm mb-6">Este nombre aparecerá en la interfaz y en los correos enviados a los usuarios.</p>

                {{-- Nombre del centro --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-300 mb-2">
                        Nombre del centro <span class="text-red-400">*</span>
                    </label>
                    <input
                        wire:model="centerName"
                        type="text"
                        placeholder="Ej: IES Gran Vía"
                        class="w-full bg-slate-900/60 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    />
                    @error('centerName')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Logo --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">
                        Logo del centro <span class="text-slate-500 font-normal">(opcional, PNG/JPG, máx. 2MB)</span>
                    </label>
                    <div class="relative border-2 border-dashed border-slate-600 rounded-xl p-6 text-center hover:border-blue-500/50 transition cursor-pointer"
                         x-data x-on:click="$refs.logoInput.click()">
                        <input x-ref="logoInput" type="file" wire:model="logo" accept="image/*" class="hidden" />

                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="h-20 mx-auto rounded-lg object-contain" />
                            <p class="mt-2 text-xs text-slate-400">{{ $logo->getClientOriginalName() }}</p>
                        @else
                            <svg class="w-10 h-10 mx-auto text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-slate-400">Haz clic para subir el logo</p>
                            <p class="text-xs text-slate-600 mt-1">PNG, JPG, SVG o WEBP</p>
                        @endif
                    </div>
                    @error('logo')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button wire:click="saveCenterInfo"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                    Continuar
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

        {{-- ══ PASO 2: Contraseña del administrador ════════════════════════════ --}}
        @elseif ($step === 2)
            <div>
                <h2 class="text-xl font-semibold text-white mb-1">Contraseña del administrador</h2>
                <p class="text-slate-400 text-sm mb-6">
                    Cambia la contraseña por defecto del panel de administración.
                    La contraseña actual es <code class="bg-slate-700 px-1.5 py-0.5 rounded text-yellow-300 text-xs">admin1234</code>.
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Nueva contraseña <span class="text-red-400">*</span></label>
                    <input wire:model="adminPassword" type="password" placeholder="Mínimo 8 caracteres"
                        class="w-full bg-slate-900/60 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                    @error('adminPassword') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Confirmar contraseña <span class="text-red-400">*</span></label>
                    <input wire:model="adminPasswordConfirm" type="password" placeholder="Repite la contraseña"
                        class="w-full bg-slate-900/60 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                    @error('adminPasswordConfirm') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <button wire:click="previousStep"
                        class="flex-1 bg-slate-700 hover:bg-slate-600 text-slate-300 font-semibold py-3 rounded-xl transition">
                        Atrás
                    </button>
                    <button wire:click="saveAdminPassword"
                        class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                        Continuar
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

        {{-- ══ PASO 3: Test de correo electrónico ══════════════════════════════ --}}
        @elseif ($step === 3)
            <div>
                <h2 class="text-xl font-semibold text-white mb-1">Correo electrónico</h2>
                <p class="text-slate-400 text-sm mb-6">
                    Envía un correo de prueba para verificar que la configuración SMTP (definida en el <code class="bg-slate-700 px-1.5 py-0.5 rounded text-xs">.env</code>) funciona correctamente.
                    Puedes omitir este paso si lo prefieres.
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Dirección de correo de prueba</label>
                    <div class="flex gap-2">
                        <input wire:model="testEmail" type="email" placeholder="admin@micentro.es"
                            class="flex-1 bg-slate-900/60 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        <button wire:click="testSmtp" wire:loading.attr="disabled"
                            class="px-5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition font-medium whitespace-nowrap">
                            <span wire:loading.remove wire:target="testSmtp">Enviar test</span>
                            <span wire:loading wire:target="testSmtp">Enviando…</span>
                        </button>
                    </div>
                    @error('testEmail') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Resultado del test --}}
                @if ($smtpStatus === 'ok')
                    <div class="flex items-center gap-3 bg-green-500/10 border border-green-500/30 rounded-xl px-4 py-3 mb-5">
                        <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-green-300 text-sm">Correo enviado correctamente. Revisa tu bandeja de entrada.</p>
                    </div>
                @elseif ($smtpStatus && str_starts_with($smtpStatus, 'error'))
                    <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 mb-5">
                        <p class="text-red-300 text-sm font-medium mb-1">Error al enviar</p>
                        <p class="text-red-400 text-xs font-mono break-all">{{ $smtpStatus }}</p>
                        <p class="text-slate-400 text-xs mt-2">Verifica los parámetros MAIL_* en tu fichero .env y reconstruye el contenedor.</p>
                    </div>
                @endif

                <div class="flex gap-3">
                    <button wire:click="previousStep"
                        class="flex-1 bg-slate-700 hover:bg-slate-600 text-slate-300 font-semibold py-3 rounded-xl transition">
                        Atrás
                    </button>
                    @if ($smtpStatus === 'ok')
                        <button wire:click="continueAfterSmtp"
                            class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                            Continuar
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <button wire:click="skipSmtp"
                            class="flex-1 bg-slate-600 hover:bg-slate-500 text-slate-200 font-semibold py-3 rounded-xl transition">
                            Omitir este paso
                        </button>
                    @endif
                </div>
            </div>

        {{-- ══ PASO 4: Completado ═══════════════════════════════════════════════ --}}
        @elseif ($step === 4)
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-500/15 border-2 border-green-400/40 mb-6">
                    <svg class="w-10 h-10 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-white mb-2">¡Todo listo!</h2>
                <p class="text-slate-400 mb-2">
                    El Gestor LDAP de <span class="text-white font-semibold">{{ \App\Models\Setting::centerName() }}</span> está configurado y listo para usar.
                </p>

                {{-- Resumen --}}
                <div class="bg-slate-900/50 border border-slate-700/50 rounded-xl p-4 mb-8 text-left space-y-2">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-slate-300">Centro configurado: <strong class="text-white">{{ \App\Models\Setting::centerName() }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-slate-300">Contraseña del administrador actualizada</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        @if ($smtpSkipped)
                            <svg class="w-4 h-4 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-slate-400">SMTP: omitido (configurable en .env)</span>
                        @else
                            <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-slate-300">SMTP verificado correctamente</span>
                        @endif
                    </div>
                </div>

                <button wire:click="complete"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Ir al panel de administración
                </button>
            </div>
        @endif

    </div>

    {{-- ── Pie ─────────────────────────────────────────────────────────────── --}}
    <p class="text-center text-slate-600 text-xs mt-6">
        Gestor LDAP · Paso {{ $step }} de {{ $totalSteps }}
    </p>

</div>
