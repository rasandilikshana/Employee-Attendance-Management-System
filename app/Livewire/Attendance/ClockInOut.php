<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class ClockInOut extends Component
{
    public $notes = '';
    public $todayAttendance = null;
    public $currentTime;

    public function mount()
    {
        $this->loadTodayAttendance();
        $this->currentTime = now()->format('H:i:s');
    }

    public function loadTodayAttendance()
    {
        $this->todayAttendance = Attendance::where('user_id', auth()->id())
            ->where('date', Carbon::today())
            ->first();
    }

    public function clockIn()
    {
        if ($this->todayAttendance && $this->todayAttendance->check_in_time) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You have already clocked in today.'
            ]);
            return;
        }

        $checkInTime = now();
        $isLate = $checkInTime->format('H:i:s') > '09:00:00';
        
        $this->todayAttendance = Attendance::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'date' => Carbon::today(),
            ],
            [
                'check_in_time' => $checkInTime,
                'status' => $isLate ? Attendance::STATUS_LATE : Attendance::STATUS_PRESENT,
                'notes' => $this->notes,
            ]
        );

        $this->notes = '';
        
        $this->dispatch('notification', [
            'type' => 'success',
            'message' => $isLate ? 'Clocked in successfully (Late)' : 'Clocked in successfully'
        ]);
    }

    public function clockOut()
    {
        if (!$this->todayAttendance || !$this->todayAttendance->check_in_time) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You must clock in first before clocking out.'
            ]);
            return;
        }

        if ($this->todayAttendance->check_out_time) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You have already clocked out today.'
            ]);
            return;
        }

        $this->todayAttendance->update([
            'check_out_time' => now(),
            'notes' => $this->notes ?: $this->todayAttendance->notes,
        ]);

        $this->todayAttendance->updateWorkHours();
        $this->loadTodayAttendance();
        $this->notes = '';

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Clocked out successfully'
        ]);
    }

    public function render()
    {
        return view('livewire.attendance.clock-in-out', [
            'currentTime' => now()->format('Y-m-d H:i:s')
        ]);
    }
}
