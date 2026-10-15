<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Auditoría')]
class AuditLogIndex extends Component
{
    use WithPagination;

    public function boot(): void
    {
        $this->authorize('manage-users');
    }

    public function render(): View
    {
        return view('livewire.admin.audit-log-index', [
            'logs' => AuditLog::query()->with('user:id,name')->latest('id')->paginate(25),
        ]);
    }
}
