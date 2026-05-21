<div class="flex-1 p-8">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Mis Aplicaciones</h1>
        <p class="text-slate-500 text-sm mt-1">Aplicaciones disponibles según tu perfil de acceso.</p>
    </div>

    {{-- Tarjeta de perfil --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-8">
        <div class="flex items-start gap-5">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0 border-2"
                 style="background: rgba(247,37,133,0.08); border-color: rgba(247,37,133,0.25)">
                <span class="font-extrabold text-2xl" style="color: #f72585">
                    {{ strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-slate-900 leading-tight">{{ $user['nombre_completo'] ?? '—' }}</h2>
                <p class="text-slate-500 text-sm mt-0.5">{{ $user['mail'] ?? '—' }}</p>
                <div class="flex flex-wrap gap-2 mt-3">
                    @forelse($user['grupos'] ?? [] as $grupo)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                              style="background: rgba(247,37,133,0.06); border-color: rgba(247,37,133,0.2); color: #c91e6d">
                            <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: #f72585"></span>
                            {{ $grupo }}
                        </span>
                    @empty
                        <span class="text-slate-400 text-xs italic">Sin grupos asignados</span>
                    @endforelse
                </div>
            </div>
            <div class="text-right flex-shrink-0 hidden sm:block">
                <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">UID</p>
                <p class="text-sm font-mono font-bold text-slate-700 mt-0.5">{{ $user['uid'] ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Grid de aplicaciones --}}
    @if(count($aplicaciones) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($aplicaciones as $app)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col
                            hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">

                    {{-- Barra superior de color --}}
                    <div class="h-1.5" style="background-color: {{ $app['color'] ?? '#f72585' }}"></div>

                    <div class="p-6 flex flex-col flex-1">
                        {{-- Icono: usa la imagen real de la app, fallback a iniciales --}}
                        <div class="w-14 h-14 rounded-xl overflow-hidden flex items-center justify-center mb-4 flex-shrink-0 border"
                             style="border-color: {{ $app['color'] ?? '#f72585' }}30; background: {{ $app['color'] ?? '#f72585' }}0d">
                            @if(file_exists(public_path('icons/' . $app['codigo'] . '.png')))
                                <img src="/icons/{{ $app['codigo'] }}.png"
                                     class="w-full h-full object-cover"
                                     alt="{{ $app['nombre'] }}">
                            @else
                                <span class="text-lg font-extrabold" style="color: {{ $app['color'] ?? '#f72585' }}">
                                    {{ strtoupper(substr($app['codigo'], 0, 2)) }}
                                </span>
                            @endif
                        </div>

                        <h3 class="text-slate-900 font-bold text-base leading-tight">{{ $app['nombre'] }}</h3>

                        @if($app['descripcion'])
                            <p class="text-slate-500 text-sm mt-1.5 flex-1 leading-relaxed">{{ $app['descripcion'] }}</p>
                        @else
                            <p class="flex-1"></p>
                        @endif

                        @if($app['url'])
                            <a href="{{ $app['url'] }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="mt-5 inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl
                                      text-sm font-bold text-white transition-opacity hover:opacity-90 active:scale-95"
                               style="background-color: {{ $app['color'] ?? '#f72585' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Acceder
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-24 bg-white rounded-2xl border border-dashed border-slate-200">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
            <h3 class="text-slate-700 font-semibold">Sin aplicaciones asignadas</h3>
            <p class="text-slate-400 text-sm mt-1 text-center max-w-xs leading-relaxed">
                Contacta con el administrador si crees que es un error.
            </p>
        </div>
    @endif

</div>
