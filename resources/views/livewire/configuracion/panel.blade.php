<div class="max-w-2xl space-y-6">

    {{-- ── Configuración SMTP editable ───────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-slate-800 font-bold text-base mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 style="color: #f72585">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Configuración de correo saliente (SMTP)
        </h2>
        <p class="text-slate-400 text-sm mb-5">Los cambios se guardan en el fichero <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs font-mono">.env</code> y se aplican de inmediato.</p>

        @if($flashSmtp)
            <div class="flex items-start gap-3 rounded-xl px-4 py-3 mb-5 border"
                 style="background: rgba(247,37,133,0.06); border-color: rgba(247,37,133,0.25)">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #f72585">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-semibold" style="color: #c91e6d">{{ $flashSmtp }}</p>
            </div>
        @endif
        @if($errorSmtp)
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-5 text-sm">{{ $errorSmtp }}</div>
        @endif

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Servidor SMTP</label>
                <input wire:model="smtpHost" type="text" placeholder="smtp.gmail.com"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800">
                @error('smtpHost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Puerto</label>
                <input wire:model="smtpPort" type="number" placeholder="587"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800">
                @error('smtpPort') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Usuario SMTP</label>
                <input wire:model="smtpUser" type="text" placeholder="usuario@dominio.com"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800">
            </div>
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Contraseña SMTP</label>
                <input wire:model="smtpPassword" type="password" placeholder="••••••••••"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800">
            </div>
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Cifrado</label>
                <select wire:model="smtpEncryption"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800 bg-white">
                    <option value="tls">TLS (recomendado)</option>
                    <option value="ssl">SSL</option>
                    <option value="">Ninguno</option>
                </select>
            </div>
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Email remitente</label>
                <input wire:model="smtpFrom" type="email" placeholder="noreply@iespacifico.es"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800">
                @error('smtpFrom') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Nombre del remitente</label>
                <input wire:model="smtpFromName" type="text" placeholder="{{ config('app.center_name') }}"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800">
            </div>
        </div>

        <button wire:click="guardarSmtp"
                wire:loading.attr="disabled"
                class="text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-opacity disabled:opacity-60"
                style="background: #f72585">
            <span wire:loading.remove wire:target="guardarSmtp">Guardar configuración</span>
            <span wire:loading wire:target="guardarSmtp">Guardando…</span>
        </button>
    </div>

    {{-- ── Test de correo ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-slate-800 font-bold text-base mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            Enviar correo de prueba
        </h2>

        @if($flashMail)
            <div class="rounded-xl px-4 py-3 mb-4 border text-sm font-semibold"
                 style="background: rgba(247,37,133,0.06); border-color: rgba(247,37,133,0.25); color: #c91e6d">
                {{ $flashMail }}
            </div>
        @endif
        @if($errorMail)
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errorMail }}</div>
        @endif

        <div class="flex gap-3">
            <input wire:model="testEmail" type="email" placeholder="destinatario@ejemplo.com"
                   class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 text-slate-800">
            <button wire:click="testSmtp"
                    wire:loading.attr="disabled"
                    class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-colors flex-shrink-0">
                <span wire:loading.remove wire:target="testSmtp">Enviar prueba</span>
                <span wire:loading wire:target="testSmtp">Enviando…</span>
            </button>
        </div>
    </div>

    {{-- ── Info LDAP (solo lectura) ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-slate-800 font-bold text-base mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
            Información del directorio LDAP
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-500">Servidor</span>
                <span class="font-semibold text-slate-800 font-mono text-xs">{{ $ldapHost }}:389</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-500">Base DN</span>
                <span class="font-semibold text-slate-800 font-mono text-xs">{{ $ldapBase }}</span>
            </div>
        </div>
    </div>

</div>
