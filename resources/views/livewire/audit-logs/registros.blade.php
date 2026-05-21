<?php
$labels = [
    'create_user'     => ['Crear usuario',      'bg-emerald-100 text-emerald-800'],
    'update_user'     => ['Editar usuario',      'bg-blue-100 text-blue-800'],
    'delete_user'     => ['Eliminar usuario',    'bg-red-100 text-red-800'],
    'reset_password'  => ['Resetear contraseña', 'bg-amber-100 text-amber-800'],
    'activate_user'   => ['Activar cuenta',      'bg-green-100 text-green-800'],
    'deactivate_user' => ['Desactivar cuenta',   'bg-slate-100 text-slate-600'],
    'create_group'    => ['Crear grupo',         'bg-teal-100 text-teal-800'],
    'delete_group'    => ['Eliminar grupo',      'bg-orange-100 text-orange-800'],
];
?>
<div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Acción</label>
                <select wire:model.live="filterAction"
                        class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Todas</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ $labels[$action][0] ?? $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Administrador</label>
                <input wire:model.live.debounce.300ms="filterAdmin" type="text" placeholder="Filtrar por admin..."
                       class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Usuario afectado</label>
                <input wire:model.live.debounce.300ms="filterTarget" type="text" placeholder="Filtrar por UID..."
                       class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <button wire:click="clearFilters"
                    class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                Limpiar
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha y hora</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Admin</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Acción</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Usuario afectado</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Detalles</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <?php [$label, $badge] = $labels[$log->action] ?? [$log->action, 'bg-slate-100 text-slate-600']; ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $log->admin_username }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-700 text-xs">{{ $log->target_uid ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs max-w-[220px] truncate"
                                title="{{ $log->details ? json_encode($log->details, JSON_UNESCAPED_UNICODE) : '' }}">
                                @if($log->details)
                                    @foreach($log->details as $k => $v)
                                        <span class="font-medium">{{ $k }}:</span>
                                        {{ is_array($v) ? implode(', ', $v) : $v }}@if(!$loop->last) &middot; @endif
                                    @endforeach
                                @else —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-400 font-mono text-xs">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                No hay registros de auditoría todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
        @endif
    </div>
    <p class="mt-3 text-xs text-slate-400 text-right">{{ $logs->total() }} registro(s) en total</p>
</div>
