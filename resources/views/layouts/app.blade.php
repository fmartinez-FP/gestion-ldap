<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="/favicon.png">
<title>Gestión {{ config('app.center_name') }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
      colors: {
        brand:  { DEFAULT: '#0d1b2a', light: '#162436', border: '#1e3550', muted: '#2e4a63' },
        accent: { DEFAULT: '#f72585', dark: '#c91e6d', light: '#ff5aa5' },
      }
    }
  }
}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  * { font-family: 'Plus Jakarta Sans', sans-serif; }
  .sidebar-item.active { background: rgba(247,37,133,0.15); color: #f72585; }
  .sidebar-item.active svg { color: #f72585; }
  .sidebar-item:hover:not(.active) { background: rgba(255,255,255,0.05); }
  ::-webkit-scrollbar { width: 4px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #1e3550; border-radius: 2px; }
</style>
@livewireStyles
</head>
<body class="h-full bg-slate-50">
<div class="flex h-full">

  {{-- ══════════════ SIDEBAR ══════════════ --}}
  <aside id="sidebar"
         class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col shadow-2xl
                transition-transform duration-300 lg:translate-x-0 -translate-x-full lg:static lg:inset-auto"
         style="background-color: #0d1b2a">

    {{-- Brand: solo favicon centrado --}}
    <div class="flex items-center justify-center px-5 py-4 border-b" style="border-color: #1e3550">
      <img src="/favicon.png" class="w-11 h-11 rounded-xl object-cover" alt="{{ config('app.center_name') }}"
           style="box-shadow: 0 0 0 2px rgba(247,37,133,0.3)">
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

      <p class="text-xs font-semibold uppercase tracking-wider px-3 mb-2" style="color: #2e4a63">Principal</p>

      <a href="{{ route('dashboard') }}"
         class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-colors
                {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Dashboard
      </a>

      <p class="text-xs font-semibold uppercase tracking-wider px-3 mb-2 mt-5" style="color: #2e4a63">Directorio</p>

      <a href="{{ route('usuarios.index') }}"
         class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-colors
                {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Usuarios
      </a>

      <a href="{{ route('aplicaciones.index') }}"
         class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-colors
                {{ request()->routeIs('aplicaciones.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
        </svg>
        Aplicaciones
      </a>

      <p class="text-xs font-semibold uppercase tracking-wider px-3 mb-2 mt-5" style="color: #2e4a63">Sistema</p>

      <a href="{{ route('admin.password') }}"
         class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-colors
                {{ request()->routeIs('admin.password') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        Cambiar contraseña
      </a>

      <a href="{{ route('configuracion') }}"
         class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-colors
                {{ request()->routeIs('configuracion') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Configuración
      </a>

      <a href="{{ route('audit.index') }}"
         class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-colors
                {{ request()->routeIs('audit.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
        Auditoría
      </a>

      <a href="{{ route('restauracion.index') }}"
         class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-colors
                {{ request()->routeIs('restauracion.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Restauracion
      </a>
    </nav>

    {{-- Logo {{ config('app.center_name') }} en footer --}}
    <div class="px-4 pt-3 pb-2">
      <div class="bg-white rounded-xl px-4 py-2.5 flex items-center justify-center">
        <img src="/images/logo.png" class="h-8 object-contain" alt="{{ config('app.center_name') }}">
      </div>
    </div>

    {{-- Admin footer --}}
    <div class="px-4 py-4 border-t" style="border-color: #1e3550">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
             style="background: rgba(247,37,133,0.2)">
          <span class="text-xs font-bold" style="color: #f72585">AD</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-white text-sm font-semibold truncate">{{ auth('admin')->user()->username }}</p>
          <p class="text-xs" style="color: #2e4a63">Administrador</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" title="Cerrar sesión"
                  class="text-slate-500 hover:text-red-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  {{-- Overlay móvil --}}
  <div id="overlay" onclick="toggleSidebar()"
       class="fixed inset-0 bg-black/60 z-40 hidden lg:hidden"></div>

  {{-- ══════════════ CONTENIDO ══════════════ --}}
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <header class="bg-white border-b border-slate-200 px-4 lg:px-8 h-14 flex items-center gap-4 flex-shrink-0">
      <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-slate-800">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
      <div class="flex-1">
        <h1 class="text-slate-800 font-bold text-base">@yield('page-title', 'Dashboard')</h1>
      </div>
      <span class="hidden sm:flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-semibold"
            style="color: #f72585; background: rgba(247,37,133,0.08)">
        <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background: #f72585"></span>
        LDAP Activo
      </span>
    </header>
    <main class="flex-1 overflow-y-auto p-4 lg:p-8">
      @yield('content')
    </main>
  </div>
</div>

<script>
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const o = document.getElementById('overlay');
  const open = !s.classList.contains('-translate-x-full');
  if (open) { s.classList.add('-translate-x-full'); o.classList.add('hidden'); }
  else { s.classList.remove('-translate-x-full'); o.classList.remove('hidden'); }
}
</script>
@livewireScripts
</body>
</html>
