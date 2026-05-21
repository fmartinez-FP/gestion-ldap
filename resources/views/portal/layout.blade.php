<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/favicon.png">
    <title>Gestión {{ config('app.center_name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        *, body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar-item.active { background: rgba(247,37,133,0.15); color: #f72585; }
        .sidebar-item:hover:not(.active) { background: rgba(255,255,255,0.05); }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1e3550; border-radius: 2px; }
    </style>
</head>
<body class="h-full bg-slate-50">
<div class="flex min-h-screen">

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col shadow-2xl"
           style="background-color: #0d1b2a">

        {{-- Brand: solo el favicon, centrado --}}
        <div class="flex items-center justify-center px-5 py-4 border-b" style="border-color: #1e3550">
            <img src="/favicon.png" class="w-11 h-11 rounded-xl object-cover" alt="{{ config('app.center_name') }}"
                 style="box-shadow: 0 0 0 2px rgba(247,37,133,0.3)">
        </div>

        {{-- User card --}}
        <div class="px-4 py-4 border-b" style="border-color: #1e3550">
            <div class="flex items-center gap-3 rounded-xl p-3" style="background: rgba(255,255,255,0.04)">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 border-2"
                     style="background: rgba(247,37,133,0.15); border-color: rgba(247,37,133,0.35)">
                    <span class="font-bold text-sm" style="color: #f72585">
                        {{ strtoupper(substr(session('ldap_user.nombre', 'U'), 0, 1)) }}
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-white text-sm font-semibold truncate leading-tight">
                        {{ session('ldap_user.nombre_completo', 'Usuario') }}
                    </p>
                    <p class="text-xs truncate mt-0.5 font-mono" style="color: #2e4a63">
                        {{ session('ldap_user.uid', '') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
            <p class="text-xs font-semibold uppercase tracking-wider px-3 mb-2" style="color: #2e4a63">Menú</p>

            <a href="{{ route('portal.dashboard') }}"
               class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 text-sm font-medium transition-colors
                      {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Mis Aplicaciones
            </a>

            <a href="{{ route('portal.password') }}"
               class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 text-sm font-medium transition-colors
                      {{ request()->routeIs('portal.password') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Cambiar Contraseña
            </a>
        </nav>

        {{-- Logo {{ config('app.center_name') }} centrado — en contenedor blanco para visibilidad --}}
        <div class="px-4 pt-3 pb-2">
            <div class="bg-white rounded-xl px-4 py-2.5 flex items-center justify-center">
                <img src="/images/logo.png" class="h-8 object-contain" alt="{{ config('app.center_name') }}">
            </div>
        </div>

        {{-- Cerrar sesión --}}
        <div class="px-3 pb-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main content ──────────────────────────────────────────────────────── --}}
    <main class="flex-1 ml-64 flex flex-col min-h-screen">
        @yield('content')
    </main>

</div>
@livewireScripts
</body>
</html>
