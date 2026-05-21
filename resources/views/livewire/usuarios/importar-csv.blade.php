<div class="space-y-6">

{{-- PASO 1: Subir archivo --}}
@if($paso === 1)
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Importar usuarios desde CSV</h2>
            <p class="text-sm text-slate-500 mt-1">El UID se genera automáticamente a partir del email (parte antes del @).</p>
        </div>
        <a href="{{ route('usuarios.plantilla') }}"
           class="flex items-center gap-2 text-sm text-emerald-600 hover:text-emerald-700 border border-emerald-200 hover:border-emerald-300 px-3 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Descargar plantilla
        </a>
    </div>

    <div class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:border-emerald-300 transition">
        <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-slate-500 text-sm mb-3">Selecciona un archivo CSV</p>
        <input type="file" wire:model="archivo" accept=".csv,.txt"
               class="block mx-auto text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 file:cursor-pointer">
        @error('archivo') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
    </div>

    <div class="mt-4 bg-slate-50 rounded-lg p-4 text-xs text-slate-500 space-y-1">
        <p class="font-semibold text-slate-600">Formato del CSV:</p>
        <code class="block font-mono">nombre,apellidos,email,grupos</code>
        <code class="block font-mono">Juan,García López,jgarcia@micentro.es,ffe;guardias</code>
        <p class="mt-2">Grupos disponibles: <span class="font-mono font-semibold text-slate-700">{{ implode(', ', $gruposValidos) }}</span> (separados por punto y coma)</p>
    </div>

    <div class="mt-4 flex justify-end">
        <button wire:click="cargarPrevia" wire:loading.attr="disabled"
                class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition disabled:opacity-50">
            <span wire:loading.remove wire:target="cargarPrevia">Previsualizar</span>
            <span wire:loading wire:target="cargarPrevia">Procesando...</span>
        </button>
    </div>
</div>
@endif

{{-- PASO 2: Previsualizar --}}
@if($paso === 2)
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div class="flex gap-3">
            @if($validos > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                ✓ {{ $validos }} listos para importar
            </span>
            @endif
            @if($invalidos > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                ✗ {{ $invalidos }} con errores (se omitirán)
            </span>
            @endif
        </div>
        <button wire:click="reiniciar" class="text-slate-400 hover:text-slate-600 text-sm">← Cambiar archivo</button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">#</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Nombre completo</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">UID</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Email</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Grupos</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($filas as $f)
                <tr class="{{ empty($f['errores']) ? 'bg-white' : 'bg-red-50' }}">
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $f['n'] }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $f['nombre'] }} {{ $f['apellidos'] }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $f['uid'] ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $f['email'] }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ implode(', ', $f['grupos']) ?: '—' }}</td>
                    <td class="px-4 py-3">
                        @if(empty($f['errores']))
                            <span class="text-xs text-emerald-600 font-medium">✓ Válido</span>
                        @else
                            <span class="text-xs text-red-600">{{ implode(' · ', $f['errores']) }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($validos > 0)
    <div class="p-4 border-t border-slate-100 flex justify-end gap-3">
        <button wire:click="reiniciar" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 transition">
            Cancelar
        </button>
        <button wire:click="importar" wire:loading.attr="disabled"
                class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition disabled:opacity-50">
            <span wire:loading.remove wire:target="importar">Importar {{ $validos }} usuario{{ $validos !== 1 ? 's' : '' }}</span>
            <span wire:loading wire:target="importar">Importando...</span>
        </button>
    </div>
    @endif
</div>
@endif

{{-- PASO 3: Resultados --}}
@if($paso === 3)
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100">
        <h2 class="font-semibold text-slate-800 mb-2">Resultado de la importación</h2>
        <div class="flex gap-3">
            @if($creados > 0)
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                ✓ {{ $creados }} creado{{ $creados !== 1 ? 's' : '' }}
            </span>
            @endif
            @if($fallidos > 0)
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                ✗ {{ $fallidos }} fallido{{ $fallidos !== 1 ? 's' : '' }}
            </span>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">UID</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Resultado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($resultado as $r)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm text-slate-700">{{ $r['uid'] }}</td>
                    <td class="px-4 py-3 text-sm {{ $r['ok'] ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $r['ok'] ? '✓' : '✗' }} {{ $r['msg'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-100 flex gap-3 justify-end">
        <button wire:click="reiniciar" class="px-4 py-2 text-sm border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition">
            Nueva importación
        </button>
        <a href="{{ route('usuarios.index') }}"
           class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition">
            Ver usuarios
        </a>
    </div>
</div>
@endif

</div>
