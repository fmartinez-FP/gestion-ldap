@extends('layouts.app')
@section('title', 'Plan de Restauracion')
@section('page-title', 'Plan de Restauracion')
@section('content')
<div class="space-y-6">

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
      <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></div>
      <div><p class="text-xs text-slate-500">Ultimo backup</p>
      <p class="text-sm font-semibold text-slate-800">{{ $ultimoBackup }}</p></div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
      <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></div>
      <div><p class="text-xs text-slate-500">Backups disponibles</p>
      <p class="text-sm font-semibold text-slate-800">{{ $numBackups }} dias</p></div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
      <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></div>
      <div><p class="text-xs text-slate-500">Espacio backups</p>
      <p class="text-sm font-semibold text-slate-800">{{ $espacioBackups }}</p></div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-semibold text-slate-800">Procedimientos de restauracion</h2>
      <a href="{{ route('restauracion.descargar') }}"
         class="flex items-center gap-2 text-sm text-emerald-600 hover:text-emerald-700 border border-emerald-200 px-3 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Descargar .md
      </a>
    </div>
    <div class="p-6 space-y-5 text-sm">

      <div class="border border-red-200 bg-red-50 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-lg">&#128308;</span>
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">ESCENARIO 1</span>
          <span class="font-semibold text-slate-800">Corrupcion de datos LDAP</span>
        </div>
        <p class="text-xs text-slate-500 mb-3">Usuarios no pueden autenticarse, errores en slapcat o arbol incompleto.</p>
        <ol class="space-y-1.5 list-decimal list-inside text-xs text-slate-600">
          <li>Identificar backup: <code class="bg-white px-1 rounded">ls -lt /opt/backups/*/ldap_*.ldif.gz | head -5</code></li>
          <li>Detener slapd: <code class="bg-white px-1 rounded">systemctl stop slapd</code></li>
          <li>Limpiar BD corrupta: <code class="bg-white px-1 rounded">rm -f /var/lib/ldap/data.mdb /var/lib/ldap/lock.mdb</code></li>
          <li>Restaurar: <code class="bg-white px-1 rounded">zcat /opt/backups/FECHA/ldap_HORA.ldif.gz | slapadd -n 1 -F /etc/ldap/slapd.d</code></li>
          <li>Permisos y reinicio: <code class="bg-white px-1 rounded">chown -R openldap:openldap /var/lib/ldap && systemctl start slapd</code></li>
        </ol>
      </div>

      <div class="border border-orange-200 bg-orange-50 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-lg">&#128992;</span>
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">ESCENARIO 2</span>
          <span class="font-semibold text-slate-800">Corrupcion de MySQL</span>
        </div>
        <p class="text-xs text-slate-500 mb-3">Panel web devuelve error 500 o tablas MySQL corruptas.</p>
        <ol class="space-y-1.5 list-decimal list-inside text-xs text-slate-600">
          <li>Identificar backup: <code class="bg-white px-1 rounded">ls -lt /opt/backups/*/gestion_ldap_panel_*.sql.gz | head -5</code></li>
          <li>Recrear BD: <code class="bg-white px-1 rounded">mysql -u root -e "DROP DATABASE gestion_ldap_panel; CREATE DATABASE gestion_ldap_panel CHARACTER SET utf8mb4;"</code></li>
          <li>Restaurar: <code class="bg-white px-1 rounded">zcat /opt/backups/FECHA/gestion_ldap_panel_HORA.sql.gz | mysql -u root gestion_ldap_panel</code></li>
          <li>Limpiar cache: <code class="bg-white px-1 rounded">cd /var/www/gestion-ldap && php artisan optimize:clear</code></li>
        </ol>
      </div>

      <div class="border border-yellow-200 bg-yellow-50 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-lg">&#128993;</span>
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">ESCENARIO 3</span>
          <span class="font-semibold text-slate-800">Usuario borrado accidentalmente</span>
        </div>
        <p class="text-xs text-slate-500 mb-3">Un usuario fue eliminado del LDAP y hay que recuperarlo.</p>
        <ol class="space-y-1.5 list-decimal list-inside text-xs text-slate-600">
          <li>Buscar en backup: <code class="bg-white px-1 rounded">zcat /opt/backups/FECHA/ldap_HORA.ldif.gz | grep -A 20 "uid=USUARIO,"</code></li>
          <li>Extraer entrada: <code class="bg-white px-1 rounded">zcat backup.ldif.gz | awk '/^dn: uid=USUARIO,/,/^$/' > /tmp/usuario.ldif</code></li>
          <li>Reimportar: <code class="bg-white px-1 rounded">ldapadd -x -H ldap://127.0.0.1 -D "cn=admin,{{ config("ldap.connections.default.base_dn") }}" -w "PASS" -f /tmp/usuario.ldif</code></li>
        </ol>
      </div>

      <div class="border border-slate-200 bg-slate-50 rounded-xl p-5">
        <h3 class="font-semibold text-slate-700 mb-3">&#9989; Verificacion post-restauracion</h3>
        <div class="font-mono text-xs text-slate-600 bg-white rounded-lg p-4 border border-slate-200 space-y-1">
          <p class="text-slate-400"># Test LDAP</p>
          <p>ldapsearch -x -H ldap://127.0.0.1 -D "cn=admin,{{ config("ldap.connections.default.base_dn") }}" -w "[TU_PASSWORD_LDAP_ADMIN]" -b "{{ config("ldap.connections.default.base_dn") }}" -s base</p>
          <p class="text-slate-400 mt-2"># Test MySQL</p>
          <p>mysql -u root gestion_ldap_panel -e "SELECT COUNT(*) FROM aplicaciones;"</p>
          <p class="text-slate-400 mt-2"># Test backup</p>
          <p>ls -lh /opt/backups/$(date +%Y-%m-%d)/</p>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100">
      <h2 class="font-semibold text-slate-800">Backups disponibles</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha</th>
            <th class="text-left px-4 py-3 font-semibold text-slate-600">LDIF</th>
            <th class="text-left px-4 py-3 font-semibold text-slate-600">MySQL</th>
            <th class="text-left px-4 py-3 font-semibold text-slate-600">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($backups as $b)
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium text-slate-800">{{ $b['fecha'] }}</td>
            <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $b['ldif'] }}</td>
            <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $b['mysql'] }}</td>
            <td class="px-4 py-3 text-slate-500 text-xs">{{ $b['total'] }}</td>
          </tr>
          @empty
          <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Sin backups encontrados</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
