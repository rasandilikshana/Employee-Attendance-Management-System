<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class PendingApprovals extends Component
{
    use WithPagination;

    public $selectedRecords = [];
    public $selectAll = false;
    public $userFilter = null;

    public function mount()
    {
        // Ensure only admin can access
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRecords = $this->getPendingAttendance()->pluck('id')->toArray();
        } else {
            $this->selectedRecords = [];
        }
    }

    public function updatedSelectedRecords()
    {
        $totalRecords = $this->getPendingAttendance()->count();
        $this->selectAll = count($this->selectedRecords) === $totalRecords;
    }

    public function getPendingAttendance()
    {
        $query = Attendance::where('is_approved', false)
            ->with(['user:id,name,email']);

        if ($this->userFilter) {
            $query->where('user_id', $this->userFilter);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function approveSelected()
    {
        if (empty($this->selectedRecords)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Please select records to approve.'
            ]);
            return;
        }

        $attendanceRecords = Attendance::whereIn('id', $this->selectedRecords)
            ->where('is_approved', false)
            ->get();

        foreach ($attendanceRecords as $attendance) {
            $attendance->approve(auth()->id());
        }

        $this->selectedRecords = [];
        $this->selectAll = false;
        $this->resetPage();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Successfully approved ' . $attendanceRecords->count() . ' records.'
        ]);
    }

    public function approveRecord($recordId)
    {
        $attendance = Attendance::find($recordId);
        
        if ($attendance && !$attendance->is_approved) {
            $attendance->approve(auth()->id());
            
            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Record approved successfully.'
            ]);
        }
    }

    public function render()
    {
        $pendingRecords = Attendance::where('is_approved', false)
            ->with(['user:id,name,email'])
            ->when($this->userFilter, function ($query) {
                $query->where('user_id', $this->userFilter);
            })
            ->orderBy('date', 'desc')
            ->paginate(15);

        $users = User::role('employee')->get(['id', 'name']);

        return view('livewire.admin.pending-approvals', [
            'pendingRecords' => $pendingRecords,
            'users' => $users
        ]);
    }
}
