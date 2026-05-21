<div class="space-y-5">

  {{-- Flash de éxito --}}
  @if($mostrarExito)
  <div wire:poll.2000ms="$set('mostrarExito', false)"
    class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 flex items-center gap-2 text-sm font-600">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ $mensajeExito }}
  </div>
  @endif

  {{-- Cabecera + botón nuevo --}}
  <div class="flex flex-col sm:flex-row sm:items-center gap-3">
    <div class="flex-1">
      <p class="text-slate-500 text-sm">
        {{ count($usuarios) }} usuario{{ count($usuarios) !== 1 ? 's' : '' }} encontrado{{ count($usuarios) !== 1 ? 's' : '' }}
      </p>
    </div>
    <a href="{{ route('usuarios.crear') }}"
      class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-sm font-600 transition-colors flex-shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Nuevo usuario
    </a>
    <a href="{{ route('usuarios.importar') }}"
      class="flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 px-4 py-2.5 rounded-xl text-sm font-600 transition-colors flex-shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
      </svg>
      Importar CSV
    </a>
    <a href="{{ route('usuarios.exportar-ldif') }}"
      class="flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 px-4 py-2.5 rounded-xl text-sm font-600 transition-colors flex-shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      Exportar LDIF
    </a>
  </div>

  {{-- Filtros --}}
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1 relative">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input wire:model.live.debounce.300ms="buscar" type="text"
          placeholder="Buscar por nombre, usuario o email..."
          class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
      </div>
      <select wire:model.live="filtroApp"
        class="border border-slate-200 rounded-xl text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-slate-600">
        <option value="">Todas las apps</option>
        @foreach($apps as $app)
          <option value="{{ $app->codigo }}">{{ $app->nombre }}</option>
        @endforeach
      </select>
      <select wire:model.live="filtroActivo"
        class="border border-slate-200 rounded-xl text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-slate-600">
        <option value="">Todos los estados</option>
        <option value="1">Activos</option>
        <option value="0">Inactivos</option>
      </select>
    </div>
  </div>

  {{-- Modal confirmación eliminar --}}
  @if($confirmarEliminar)
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
      <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
      </div>
      <h3 class="text-slate-900 font-700 text-center mb-2">¿Eliminar usuario?</h3>
      <p class="text-slate-500 text-sm text-center mb-5">
        El usuario <strong class="text-slate-800">{{ $confirmarEliminar }}</strong> será eliminado del directorio LDAP y de todos sus grupos. Esta acción no se puede deshacer.
      </p>
      <div class="flex gap-3">
        <button wire:click="cancelarDelete"
          class="flex-1 py-2.5 border border-slate-200 rounded-xl text-slate-700 text-sm font-600 hover:bg-slate-50 transition-colors">
          Cancelar
        </button>
        <button wire:click="eliminarUsuario"
          class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-600 transition-colors">
          Eliminar
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Tabla / cards de usuarios --}}
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

    @if(count($usuarios) === 0)
    <div class="flex flex-col items-center py-16 text-slate-400">
      <svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      <p class="font-600">No se encontraron usuarios</p>
      <p class="text-sm mt-1">Prueba a cambiar los filtros</p>
    </div>
    @else

    {{-- Desktop: tabla --}}
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-100">
          <tr>
            <th class="text-left text-xs font-700 text-slate-500 uppercase tracking-wider px-5 py-3">Usuario</th>
            <th class="text-left text-xs font-700 text-slate-500 uppercase tracking-wider px-5 py-3">Aplicaciones</th>
            <th class="text-left text-xs font-700 text-slate-500 uppercase tracking-wider px-5 py-3">Estado</th>
            <th class="text-right text-xs font-700 text-slate-500 uppercase tracking-wider px-5 py-3">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          @foreach($usuarios as $u)
          <tr class="hover:bg-slate-50/50 transition-colors">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
                  <span class="text-sm font-700 text-slate-600">
                    {{ strtoupper(substr($u['nombre'], 0, 1)) }}{{ strtoupper(substr($u['apellidos'], 0, 1)) }}
                  </span>
                </div>
                <div>
                  <p class="text-slate-900 font-600 text-sm">{{ $u['nombre_completo'] }}</p>
                  <p class="text-slate-400 text-xs">{{ $u['uid'] }} · {{ $u['mail'] }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex flex-wrap gap-1">
                @forelse($u['grupos'] as $grupo)
                  @php $appInfo = $apps->firstWhere('codigo', $grupo); @endphp
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-600"
                    style="background-color: {{ $appInfo?->color ?? '#94a3b8' }}20; color: {{ $appInfo?->color ?? '#94a3b8' }}">
                    {{ $appInfo?->nombre ?? $grupo }}
                  </span>
                @empty
                  <span class="text-slate-400 text-xs">Sin acceso</span>
                @endforelse
              </div>
            </td>
            <td class="px-5 py-3.5">
              <button wire:click="toggleActivo('{{ $u['uid'] }}')"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-600 transition-colors
                  {{ $u['activo'] ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $u['activo'] ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                {{ $u['activo'] ? 'Activo' : 'Inactivo' }}
              </button>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center justify-end gap-1">
                <a href="{{ route('usuarios.editar', $u['uid']) }}"
                  title="Editar"
                  class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center transition-colors text-slate-500 hover:text-slate-800">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </a>
                <button wire:click="resetearContrasena('{{ $u['uid'] }}')"
                  title="Resetear contraseña"
                  class="w-8 h-8 rounded-lg hover:bg-amber-50 flex items-center justify-center transition-colors text-slate-500 hover:text-amber-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                  </svg>
                </button>
                <button wire:click="confirmarDelete('{{ $u['uid'] }}')"
                  title="Eliminar"
                  class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors text-slate-500 hover:text-red-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Mobile: cards --}}
    <div class="md:hidden divide-y divide-slate-100">
      @foreach($usuarios as $u)
      <div class="p-4 space-y-3">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
              <span class="text-sm font-700 text-slate-600">
                {{ strtoupper(substr($u['nombre'], 0, 1)) }}{{ strtoupper(substr($u['apellidos'], 0, 1)) }}
              </span>
            </div>
            <div>
              <p class="text-slate-900 font-600 text-sm">{{ $u['nombre_completo'] }}</p>
              <p class="text-slate-400 text-xs">{{ $u['uid'] }}</p>
            </div>
          </div>
          <button wire:click="toggleActivo('{{ $u['uid'] }}')"
            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-600
              {{ $u['activo'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $u['activo'] ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
            {{ $u['activo'] ? 'Activo' : 'Inactivo' }}
          </button>
        </div>
        <p class="text-slate-400 text-xs">{{ $u['mail'] }}</p>
        <div class="flex flex-wrap gap-1">
          @foreach($u['grupos'] as $grupo)
            @php $appInfo = $apps->firstWhere('codigo', $grupo); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs font-600"
              style="background-color: {{ $appInfo?->color ?? '#94a3b8' }}20; color: {{ $appInfo?->color ?? '#94a3b8' }}">
              {{ $appInfo?->nombre ?? $grupo }}
            </span>
          @endforeach
        </div>
        <div class="flex gap-2 pt-1">
          <a href="{{ route('usuarios.editar', $u['uid']) }}"
            class="flex-1 flex items-center justify-center gap-1.5 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 font-600 hover:bg-slate-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Editar
          </a>
          <button wire:click="confirmarDelete('{{ $u['uid'] }}')"
            class="flex items-center justify-center px-3 py-2 border border-red-100 text-red-500 rounded-lg hover:bg-red-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </div>
      @endforeach
    </div>

    @endif
  </div>
</div>
