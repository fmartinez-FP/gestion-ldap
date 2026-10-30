{{-- resources/views/livewire/aplicaciones/grupos-globales.blade.php --}}
<div class="space-y-4">

  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-slate-800 font-700 text-base">Grupos globales</h2>
      <p class="text-slate-500 text-xs mt-0.5">Grupos sin aplicación asociada. No aparecen en el lanzador de aplicaciones; cada aplicación decide qué hacer con ellos según el atributo <code>memberOf</code> del usuario.</p>
    </div>
  </div>

  @if($flash && !$grupoAbierto)
  <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-600">{{ $flash }}</div>
  @endif

  @if(count($grupos) === 0)
  <div class="bg-white rounded-2xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-sm">
    Todavía no hay grupos globales. Créalos con «Nueva aplicación o grupo» → «Grupo global (sin aplicación)».
  </div>
  @else
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($grupos as $g)
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
      <div class="flex items-start justify-between gap-2">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <div>
            <p class="text-slate-900 font-700 text-sm font-mono">{{ $g['cn'] }}</p>
            <p class="text-slate-400 text-xs">{{ count($g['miembros']) }} miembro{{ count($g['miembros']) !== 1 ? 's' : '' }}</p>
          </div>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-700 bg-violet-50 text-violet-700 flex-shrink-0">Global</span>
      </div>

      @if($g['description'])
      <p class="text-slate-500 text-xs">{{ $g['description'] }}</p>
      @endif

      <button wire:click="gestionar('{{ $g['cn'] }}')"
        class="w-full flex items-center justify-center gap-1.5 py-2 border border-slate-200 rounded-lg text-xs text-slate-600 font-600 hover:bg-slate-50">
        Gestionar miembros
      </button>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Modal de miembros --}}
  @if($abierto)
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
      <div class="flex items-start justify-between gap-3 mb-4">
        <div>
          <h3 class="text-slate-800 font-700">Miembros de <span class="font-mono">{{ $abierto['cn'] }}</span></h3>
          @if($abierto['description'])<p class="text-slate-500 text-xs mt-0.5">{{ $abierto['description'] }}</p>@endif
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-700 bg-violet-50 text-violet-700">Global</span>
      </div>

      @if($error)
      <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-3 py-2 text-xs font-600 mb-3">{{ $error }}</div>
      @endif
      @if($flash)
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-3 py-2 text-xs font-600 mb-3">{{ $flash }}</div>
      @endif

      {{-- Añadir --}}
      <div class="flex gap-2 mb-4">
        <select wire:model="uidNuevo"
          class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-500">
          <option value="">— Añadir usuario —</option>
          @foreach($candidatos as $u)
            <option value="{{ $u['uid'] }}">{{ trim($u['apellidos'] . ', ' . $u['nombre'], ', ') }} ({{ $u['uid'] }})</option>
          @endforeach
        </select>
        <button wire:click="anadirMiembro"
          class="px-4 py-2 bg-violet-500 hover:bg-violet-600 text-white rounded-xl text-sm font-700 transition-colors">
          Añadir
        </button>
      </div>

      {{-- Lista --}}
      <div class="overflow-y-auto divide-y divide-slate-100 border border-slate-100 rounded-xl">
        @foreach($miembros as $m)
        <div class="flex items-center justify-between gap-3 px-3 py-2.5">
          <div class="min-w-0">
            <p class="text-slate-800 text-sm font-600 truncate">{{ $m['nombre'] }}</p>
            <p class="text-slate-400 text-xs font-mono">{{ $m['uid'] }}</p>
          </div>
          @if(count($miembros) > 1)
          <button wire:click="quitarMiembro('{{ $m['uid'] }}')"
            wire:confirm="¿Quitar a {{ $m['uid'] }} del grupo {{ $abierto['cn'] }}?"
            class="text-xs text-red-500 hover:text-red-700 font-600 px-2 py-1 rounded-lg hover:bg-red-50">
            Quitar
          </button>
          @else
          <span class="text-xs text-slate-400" title="Un grupo necesita al menos un miembro">Último miembro</span>
          @endif
        </div>
        @endforeach
      </div>
      <p class="text-xs text-slate-400 mt-2">El último miembro no se puede quitar: un grupo necesita al menos uno.</p>

      <div class="mt-5">
        <button wire:click="cerrar"
          class="w-full py-2.5 border border-slate-200 rounded-xl text-slate-600 text-sm font-600 hover:bg-slate-50">
          Cerrar
        </button>
      </div>
    </div>
  </div>
  @endif

</div>
