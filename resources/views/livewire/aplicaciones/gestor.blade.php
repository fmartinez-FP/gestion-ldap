{{-- resources/views/livewire/aplicaciones/gestor.blade.php --}}
<div class="space-y-5">

  {{-- Flash --}}
  @if($flash)
  <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-600 flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ $flash }}
  </div>
  @endif

  <div class="flex items-center justify-between">
    <p class="text-slate-500 text-sm">{{ $apps->count() }} aplicación{{ $apps->count() !== 1 ? 'es' : '' }} registrada{{ $apps->count() !== 1 ? 's' : '' }}</p>
    <button wire:click="nueva"
      class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-sm font-600 transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Nueva aplicación
    </button>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($apps as $app)
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
      <div class="flex items-start justify-between gap-2">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
            style="background-color: {{ $app->color }}20;">
            <span class="text-sm font-800" style="color: {{ $app->color }}">
              {{ strtoupper(substr($app->codigo, 0, 2)) }}
            </span>
          </div>
          <div>
            <p class="text-slate-900 font-700 text-sm">{{ $app->nombre }}</p>
            <p class="text-slate-400 text-xs font-mono">{{ $app->codigo }}</p>
          </div>
        </div>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-600 flex-shrink-0
          {{ $app->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
          <span class="w-1.5 h-1.5 rounded-full {{ $app->activo ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
          {{ $app->activo ? 'Activa' : 'Inactiva' }}
        </span>
      </div>

      @if($app->descripcion)
      <p class="text-slate-500 text-xs">{{ $app->descripcion }}</p>
      @endif

      @if($app->url)
      <a href="{{ $app->url }}" target="_blank"
        class="inline-flex items-center gap-1.5 text-xs text-blue-500 hover:text-blue-700 font-600">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
        {{ $app->url }}
      </a>
      @endif

      <div class="flex gap-2 pt-1">
        <button wire:click="editar({{ $app->id }})"
          class="flex-1 flex items-center justify-center gap-1.5 py-2 border border-slate-200 rounded-lg text-xs text-slate-600 font-600 hover:bg-slate-50">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
          Editar
        </button>
        @if($app->activo)
        <button wire:click="eliminar({{ $app->id }})"
          class="flex items-center justify-center px-3 py-2 border border-amber-100 text-amber-600 rounded-lg hover:bg-amber-50 text-xs">
          Desactivar
        </button>
        @endif
      </div>
    </div>
    @endforeach
  </div>

  {{-- Info escalabilidad --}}
  <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-start gap-3">
    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
      <p class="text-blue-800 text-sm font-600">Sistema escalable</p>
      <p class="text-blue-600 text-xs mt-0.5">Al crear una nueva aplicación, se genera automáticamente el grupo LDAP correspondiente. Los nuevos usuarios podrán ser asignados a ese grupo de inmediato.</p>
    </div>
  </div>

  {{-- Modal crear/editar app --}}
  @if($mostrarModal)
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
      <h3 class="text-slate-800 font-700 mb-5">{{ $editandoId ? 'Editar aplicación' : 'Nueva aplicación' }}</h3>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-600 text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
          <input wire:model="nombre" type="text" placeholder="Gestión de Tutorías"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        @if(!$editandoId)
        <div>
          <label class="block text-sm font-600 text-slate-700 mb-1.5">Código (para LDAP) <span class="text-red-500">*</span></label>
          <input wire:model="codigo" type="text" placeholder="tutorias"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <p class="text-xs text-slate-400 mt-1">Sólo letras minúsculas, números y guiones. Se usará como nombre del grupo LDAP.</p>
          @error('codigo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        @endif

        <div>
          <label class="block text-sm font-600 text-slate-700 mb-1.5">Descripción</label>
          <input wire:model="descripcion" type="text" placeholder="Gestión de tutorías y seguimiento"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div>
          <label class="block text-sm font-600 text-slate-700 mb-1.5">URL de acceso</label>
          <input wire:model="url" type="url" placeholder="http://172.16.90.55:8084"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="flex items-center gap-4">
          <div class="flex-1">
            <label class="block text-sm font-600 text-slate-700 mb-1.5">Color identificativo</label>
            <input wire:model="color" type="color"
              class="h-10 w-full rounded-xl border border-slate-200 cursor-pointer p-1">
          </div>
          <div>
            <label class="block text-sm font-600 text-slate-700 mb-1.5">Orden</label>
            <input wire:model="orden" type="number" min="0"
              class="w-20 px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
      </div>

      <div class="flex gap-3 mt-6">
        <button wire:click="$set('mostrarModal', false)"
          class="flex-1 py-2.5 border border-slate-200 rounded-xl text-slate-600 text-sm font-600 hover:bg-slate-50">
          Cancelar
        </button>
        <button wire:click="guardar"
          class="flex-1 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-700 transition-colors">
          {{ $editandoId ? 'Guardar' : 'Crear aplicación' }}
        </button>
      </div>
    </div>
  </div>
  @endif

</div>
