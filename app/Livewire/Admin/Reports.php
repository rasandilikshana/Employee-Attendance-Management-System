<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Reports extends Component
{
    public $dateFrom = '';
    public $dateTo = '';
    public $employeeFilter = '';
    public $reportType = 'summary';

    public function mount()
    {
        // Ensure only admin can access
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        // Set default date range to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function exportReport()
    {
        $filename = 'attendance_report_' . now()->format('Y_m_d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            if ($this->reportType === 'detailed') {
                // Detailed report with individual records
                fputcsv($file, ['Employee', 'Email', 'Date', 'Clock In', 'Clock Out', 'Work Hours', 'Status', 'Approved']);
                
                $records = $this->getDetailedReportData();
                foreach ($records as $record) {
                    fputcsv($file, [
                        $record->user->name,
                        $record->user->email,
                        $record->date->format('Y-m-d'),
                        $record->check_in_time ? $record->check_in_time->format('H:i:s') : '',
                        $record->check_out_time ? $record->check_out_time->format('H:i:s') : '',
                        $record->work_hours ? number_format(abs($record->work_hours), 2) : '',
                        ucfirst($record->status),
                        $record->is_approved ? 'Yes' : 'No'
                    ]);
                }
            } else {
                // Summary report by employee
                fputcsv($file, ['Employee', 'Email', 'Total Days', 'Present Days', 'Late Days', 'Total Hours', 'Approved Records', 'Pending Records']);
                
                $summaryData = $this->getSummaryReportData();
                foreach ($summaryData as $data) {
                    fputcsv($file, [
                        $data['user']->name,
                        $data['user']->email,
                        $data['total_days'],
                        $data['present_days'],
                        $data['late_days'],
                        number_format($data['total_hours'], 2),
                        $data['approved_records'],
                        $data['pending_records']
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    private function getDetailedReportData()
    {
        $query = Attendance::with(['user:id,name,email']);

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->employeeFilter) {
            $query->where('user_id', $this->employeeFilter);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    private function getSummaryReportData()
    {
        $usersQuery = User::role('employee');
        
        if ($this->employeeFilter) {
            $usersQuery->where('id', $this->employeeFilter);
        }

        $users = $usersQuery->get();
        $summaryData = [];

        foreach ($users as $user) {
            $attendanceQuery = Attendance::where('user_id', $user->id);

            if ($this->dateFrom) {
                $attendanceQuery->whereDate('date', '>=', $this->dateFrom);
            }

            if ($this->dateTo) {
                $attendanceQuery->whereDate('date', '<=', $this->dateTo);
            }

            $attendance = $attendanceQuery->get();

            $summaryData[] = [
                'user' => $user,
                'total_days' => $attendance->count(),
                'present_days' => $attendance->where('status', '!=', 'absent')->count(),
                'late_days' => $attendance->where('status', 'late')->count(),
                'total_hours' => abs($attendance->sum('work_hours')),
                'approved_records' => $attendance->where('is_approved', true)->count(),
                'pending_records' => $attendance->where('is_approved', false)->count(),
            ];
        }

        return $summaryData;
    }

    public function render()
    {
        // Overall statistics
        $overallStats = [
            'total_employees' => User::role('employee')->count(),
            'total_attendance_records' => Attendance::count(),
            'pending_approvals' => Attendance::where('is_approved', false)->count(),
            'total_work_hours' => abs(Attendance::sum('work_hours')),
        ];

        // Monthly trends (last 6 months)
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyTrends[] = [
                'month' => $month->format('M Y'),
                'records' => Attendance::whereYear('date', $month->year)
                    ->whereMonth('date', $month->month)
                    ->count(),
                'hours' => abs(Attendance::whereYear('date', $month->year)
                    ->whereMonth('date', $month->month)
                    ->sum('work_hours')),
            ];
        }

        // Top performers (by hours worked)
        $topPerformers = User::role('employee')
            ->with(['attendances' => function($query) {
                if ($this->dateFrom) {
                    $query->whereDate('date', '>=', $this->dateFrom);
                }
                if ($this->dateTo) {
                    $query->whereDate('date', '<=', $this->dateTo);
                }
            }])
            ->get()
            ->map(function($user) {
                return [
                    'user' => $user,
                    'total_hours' => abs($user->attendances->sum('work_hours')),
                    'present_days' => $user->attendances->where('status', '!=', 'absent')->count(),
                ];
            })
            ->sortByDesc('total_hours')
            ->take(5);

        // Late arrivals summary
        $lateArrivals = Attendance::where('status', 'late')
            ->when($this->dateFrom, function($query) {
                $query->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($query) {
                $query->whereDate('date', '<=', $this->dateTo);
            })
            ->with(['user:id,name'])
            ->get()
            ->groupBy('user_id')
            ->map(function($records) {
                return [
                    'user' => $records->first()->user,
                    'late_count' => $records->count(),
                    'latest_record' => $records->sortByDesc('date')->first()
                ];
            })
            ->sortByDesc('late_count')
            ->take(10);

        // Get employees for filter
        $employees = User::role('employee')->select('id', 'name')->orderBy('name')->get();

        // Generate report data based on type
        $reportData = [];
        if ($this->reportType === 'detailed') {
            $reportData = $this->getDetailedReportData()->take(50); // Limit for display
        } else {
            $reportData = $this->getSummaryReportData();
        }

        return view('livewire.admin.reports', [
            'overallStats' => $overallStats,
            'monthlyTrends' => $monthlyTrends,
            'topPerformers' => $topPerformers,
            'lateArrivals' => $lateArrivals,
            'employees' => $employees,
            'reportData' => $reportData
        ]);
    }
}
