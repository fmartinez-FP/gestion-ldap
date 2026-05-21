<?php
namespace App\Livewire\AuditLogs;
use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class Registros extends Component
{
    use WithPagination;

    public string $filterAction = '';
    public string $filterAdmin  = '';
    public string $filterTarget = '';

    public function updatingFilterAction(): void { $this->resetPage(); }
    public function updatingFilterAdmin(): void  { $this->resetPage(); }
    public function updatingFilterTarget(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->filterAction = $this->filterAdmin = $this->filterTarget = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::query()->latest('created_at');
        if ($this->filterAction) $query->where('action', $this->filterAction);
        if ($this->filterAdmin)  $query->where('admin_username', 'like', "%{$this->filterAdmin}%");
        if ($this->filterTarget) $query->where('target_uid',     'like', "%{$this->filterTarget}%");

        return view('livewire.audit-logs.registros', [
            'logs'    => $query->paginate(20),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
