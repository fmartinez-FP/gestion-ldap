@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
  use App\Services\LdapService;
  use App\Models\Aplicacion;
  $ldap  = app(LdapService::class);
  $stats = $ldap->getStats();
  $apps  = Aplicacion::where('activo', true)->orderBy('orden')->get();
@endphp

<div class="space-y-6">

  {{-- ── Stats ────────────────────────────────────────────────────────────── --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <span class="text-slate-500 text-sm font-semibold">Total usuarios</span>
        <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
      </div>
      <p class="text-3xl font-extrabold text-slate-900">{{ $stats['total'] }}</p>
      <p class="text-xs text-slate-400 mt-1">en el directorio LDAP</p>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <span class="text-slate-500 text-sm font-semibold">Activos</span>
        <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
      </div>
      <p class="text-3xl font-extrabold text-slate-900">{{ $stats['activos'] }}</p>
      <p class="text-xs text-slate-400 mt-1">cuentas habilitadas</p>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <span class="text-slate-500 text-sm font-semibold">Inactivos</span>
        <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
        </div>
      </div>
      <p class="text-3xl font-extrabold text-slate-900">{{ $stats['inactivos'] }}</p>
      <p class="text-xs text-slate-400 mt-1">cuentas deshabilitadas</p>
    </div>
  </div>

  {{-- ── Acceso directo a aplicaciones ──────────────────────────────────── --}}
  @if($apps->count() > 0)
  <div>
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-slate-800 font-bold text-base">Acceso a aplicaciones</h2>
      <a href="{{ route('aplicaciones.index') }}"
         class="text-sm font-semibold" style="color: #f72585">Gestionar →</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      @foreach($apps as $app)
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden
                  hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col">
        {{-- Barra de color --}}
        <div class="h-1.5" style="background-color: {{ $app->color }}"></div>
        <div class="p-5 flex flex-col flex-1">
          <div class="flex items-center gap-3 mb-3">
            {{-- Icono real si existe, fallback a iniciales --}}
            <div class="w-11 h-11 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0 border"
                 style="border-color: {{ $app->color }}30; background: {{ $app->color }}0d">
              @if(file_exists(public_path('icons/' . $app->codigo . '.png')))
                <img src="/icons/{{ $app->codigo }}.png" class="w-full h-full object-cover" alt="{{ $app->nombre }}">
              @else
                <span class="text-sm font-extrabold" style="color: {{ $app->color }}">
                  {{ strtoupper(substr($app->codigo, 0, 2)) }}
                </span>
              @endif
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="text-slate-900 font-bold text-sm leading-tight truncate">{{ $app->nombre }}</h3>
              @if($app->descripcion)
                <p class="text-slate-400 text-xs mt-0.5 truncate">{{ $app->descripcion }}</p>
              @endif
            </div>
          </div>

          @if($app->url)
          <a href="{{ $app->url }}" target="_blank" rel="noopener noreferrer"
             class="mt-auto inline-flex items-center justify-center gap-2 w-full px-4 py-2.5
                    rounded-xl text-sm font-bold text-white transition-opacity hover:opacity-90"
             style="background-color: {{ $app->color }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Abrir aplicación
          </a>
          @endif
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Acciones rápidas ────────────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
    <h2 class="text-slate-800 font-bold mb-4">Acciones rápidas</h2>
    <div class="flex flex-wrap gap-3">
      <a href="{{ route('usuarios.crear') }}"
         class="flex items-center gap-2 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-opacity hover:opacity-90"
         style="background: #f72585">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo usuario
      </a>
      <a href="{{ route('usuarios.index') }}"
         class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700
                px-4 py-2.5 rounded-xl text-sm font-bold transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
        </svg>
        Ver todos los usuarios
      </a>
    </div>
  </div>

</div>
@endsection
