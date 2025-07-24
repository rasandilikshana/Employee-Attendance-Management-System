<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class History extends Component
{
    use WithPagination;

    public $dateFrom = '';
    public $dateTo = '';
    public $statusFilter = '';
    public $approvalFilter = '';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingApprovalFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['dateFrom', 'dateTo', 'statusFilter', 'approvalFilter']);
        $this->mount();
        $this->resetPage();
    }

    public function exportData()
    {
        $attendance = $this->getFilteredAttendance();

        $filename = 'attendance_history_' . now()->format('Y_m_d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($attendance) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Date', 'Clock In', 'Clock Out', 'Work Hours', 'Status', 'Approved', 'Notes']);

            foreach ($attendance as $record) {
                fputcsv($file, [
                    $record->date->format('Y-m-d'),
                    $record->check_in_time ? $record->check_in_time->format('H:i:s') : '',
                    $record->check_out_time ? $record->check_out_time->format('H:i:s') : '',
                    $record->work_hours ? number_format(abs($record->work_hours), 2) : '',
                    ucfirst($record->status),
                    $record->is_approved ? 'Yes' : 'No',
                    $record->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    private function getFilteredAttendance()
    {
        $query = Attendance::where('user_id', auth()->id());

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->approvalFilter !== '') {
            $query->where('is_approved', $this->approvalFilter === '1');
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function render()
    {
        $query = Attendance::where('user_id', auth()->id());

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->approvalFilter !== '') {
            $query->where('is_approved', $this->approvalFilter === '1');
        }

        $attendanceRecords = $query->orderBy('date', 'desc')->paginate(15);

        $allRecords = $this->getFilteredAttendance();
        $summaryStats = [
            'total_days' => $allRecords->count(),
            'total_hours' => abs($allRecords->sum('work_hours')),
            'present_days' => $allRecords->where('status', '!=', 'absent')->count(),
            'late_days' => $allRecords->where('status', 'late')->count(),
            'approved_records' => $allRecords->where('is_approved', true)->count(),
            'pending_records' => $allRecords->where('is_approved', false)->count(),
        ];

        return view('livewire.attendance.history', [
            'attendanceRecords' => $attendanceRecords,
            'summaryStats' => $summaryStats
        ]);
    }
}
