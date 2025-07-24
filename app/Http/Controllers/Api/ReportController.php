<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{

    /**
     * Get attendance summary report
     */
    public function attendanceSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only admin can view all users' reports, employees see their own
        if (!$user->hasRole('admin')) {
            $request->merge(['user_id' => $user->id]);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|integer|exists:users,id',
            'department' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $query = Attendance::betweenDates($startDate, $endDate)
                ->with(['user:id,name,email']);

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            $attendanceRecords = $query->get();

            // Group by user and calculate statistics
            $summary = $attendanceRecords->groupBy('user_id')->map(function ($userAttendance, $userId) {
                $user = $userAttendance->first()->user;
                
                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'statistics' => [
                        'total_days' => $userAttendance->count(),
                        'present_days' => $userAttendance->where('status', 'present')->count(),
                        'late_days' => $userAttendance->where('status', 'late')->count(),
                        'partial_days' => $userAttendance->where('status', 'partial')->count(),
                        'absent_days' => $userAttendance->where('status', 'absent')->count(),
                        'total_work_hours' => round($userAttendance->sum('work_hours'), 2),
                        'average_work_hours' => round($userAttendance->avg('work_hours'), 2),
                        'approved_records' => $userAttendance->where('is_approved', true)->count(),
                        'pending_records' => $userAttendance->where('is_approved', false)->count(),
                    ]
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'total_days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
                    ],
                    'summary' => $summary
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily attendance report
     */
    public function dailyAttendance(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $date = $request->date;

            $attendance = Attendance::where('date', $date)
                ->with(['user:id,name,email', 'approver:id,name'])
                ->orderBy('check_in_time')
                ->get();

            // Get all users to show who didn't clock in
            $allUsers = User::role('employee')->get(['id', 'name', 'email']);
            $presentUserIds = $attendance->pluck('user_id')->toArray();
            
            $absentUsers = $allUsers->filter(function ($user) use ($presentUserIds) {
                return !in_array($user->id, $presentUserIds);
            })->values();

            $statistics = [
                'total_employees' => $allUsers->count(),
                'present' => $attendance->where('status', '!=', 'absent')->count(),
                'absent' => $absentUsers->count(),
                'late' => $attendance->where('status', 'late')->count(),
                'on_time' => $attendance->where('status', 'present')->count(),
                'partial' => $attendance->where('status', 'partial')->count(),
                'total_work_hours' => round($attendance->sum('work_hours'), 2),
                'average_work_hours' => round($attendance->avg('work_hours'), 2),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'statistics' => $statistics,
                    'attendance_records' => $attendance,
                    'absent_employees' => $absentUsers,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate daily report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly attendance report
     */
    public function monthlyAttendance(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('admin')) {
            $request->merge(['user_id' => $user->id]);
        }

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $year = $request->year;
            $month = $request->month;
            
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $query = Attendance::betweenDates($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
                ->with(['user:id,name,email']);

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            $attendanceRecords = $query->get();

            // Create calendar view
            $calendar = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                $dateString = $currentDate->format('Y-m-d');
                $dayAttendance = $attendanceRecords->where('date', $dateString);
                
                $calendar[$dateString] = [
                    'date' => $dateString,
                    'day_name' => $currentDate->format('l'),
                    'is_weekend' => $currentDate->isWeekend(),
                    'attendance' => $dayAttendance->values(),
                    'statistics' => [
                        'total_present' => $dayAttendance->where('status', '!=', 'absent')->count(),
                        'total_late' => $dayAttendance->where('status', 'late')->count(),
                        'total_work_hours' => round($dayAttendance->sum('work_hours'), 2),
                    ]
                ];
                
                $currentDate->addDay();
            }

            // Overall monthly statistics
            $monthlyStats = [
                'total_working_days' => $startDate->diffInWeekdays($endDate) + 1,
                'total_attendance_records' => $attendanceRecords->count(),
                'total_present_days' => $attendanceRecords->where('status', '!=', 'absent')->count(),
                'total_late_days' => $attendanceRecords->where('status', 'late')->count(),
                'total_work_hours' => round($attendanceRecords->sum('work_hours'), 2),
                'average_daily_hours' => round($attendanceRecords->avg('work_hours'), 2),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => $startDate->format('F'),
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ],
                    'statistics' => $monthlyStats,
                    'calendar' => $calendar,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate monthly report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance trends and analytics
     */
    public function attendanceTrends(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'period' => 'required|in:week,month,quarter,year',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = $request->period;
            $now = Carbon::now();

            // Determine date range based on period
            switch ($period) {
                case 'week':
                    $startDate = $now->copy()->subWeeks(4)->startOfWeek();
                    $groupBy = 'week';
                    break;
                case 'month':
                    $startDate = $now->copy()->subMonths(6)->startOfMonth();
                    $groupBy = 'month';
                    break;
                case 'quarter':
                    $startDate = $now->copy()->subQuarters(4)->startOfQuarter();
                    $groupBy = 'quarter';
                    break;
                case 'year':
                    $startDate = $now->copy()->subYears(3)->startOfYear();
                    $groupBy = 'year';
                    break;
            }

            $query = Attendance::where('date', '>=', $startDate->format('Y-m-d'));

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // Get trends data
            $trendsData = $query->selectRaw('
                DATE(date) as date,
                COUNT(*) as total_records,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
                AVG(work_hours) as avg_work_hours,
                SUM(work_hours) as total_work_hours
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // Performance metrics
            $performanceMetrics = [
                'attendance_rate' => $this->calculateAttendanceRate($startDate, $now),
                'punctuality_rate' => $this->calculatePunctualityRate($startDate, $now),
                'average_work_hours' => $this->calculateAverageWorkHours($startDate, $now),
                'approval_rate' => $this->calculateApprovalRate($startDate, $now),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'type' => $period,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $now->format('Y-m-d'),
                    ],
                    'trends' => $trendsData,
                    'performance_metrics' => $performanceMetrics,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate trends report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance data (CSV format)
     */
    public function exportAttendance(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('admin')) {
            $request->merge(['user_id' => $user->id]);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|integer|exists:users,id',
            'format' => 'nullable|in:csv,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $format = $request->input('format', 'json');

            $query = Attendance::betweenDates($startDate, $endDate)
                ->with(['user:id,name,email', 'approver:id,name']);

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            $attendanceData = $query->orderBy('date', 'desc')
                ->orderBy('check_in_time')
                ->get()
                ->map(function ($attendance) {
                    return [
                        'date' => $attendance->date,
                        'employee_name' => $attendance->user->name,
                        'employee_email' => $attendance->user->email,
                        'check_in_time' => $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : null,
                        'check_out_time' => $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : null,
                        'work_hours' => $attendance->work_hours,
                        'status' => $attendance->status,
                        'is_approved' => $attendance->is_approved ? 'Yes' : 'No',
                        'approved_by' => $attendance->approver?->name,
                        'approved_at' => $attendance->approved_at?->format('Y-m-d H:i:s'),
                        'notes' => $attendance->notes,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Attendance data exported successfully',
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'total_records' => $attendanceData->count(),
                    'format' => $format,
                    'attendance_data' => $attendanceData,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to calculate attendance rate
     */
    private function calculateAttendanceRate($startDate, $endDate): float
    {
        $totalWorkingDays = $startDate->diffInWeekdays($endDate);
        $totalUsers = User::role('employee')->count();
        $expectedAttendance = $totalWorkingDays * $totalUsers;
        
        $actualAttendance = Attendance::betweenDates($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->where('status', '!=', 'absent')
            ->count();

        return $expectedAttendance > 0 ? round(($actualAttendance / $expectedAttendance) * 100, 2) : 0;
    }

    /**
     * Helper method to calculate punctuality rate
     */
    private function calculatePunctualityRate($startDate, $endDate): float
    {
        $totalAttendance = Attendance::betweenDates($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->where('status', '!=', 'absent')
            ->count();
        
        $onTimeAttendance = Attendance::betweenDates($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->where('status', 'present')
            ->count();

        return $totalAttendance > 0 ? round(($onTimeAttendance / $totalAttendance) * 100, 2) : 0;
    }

    /**
     * Helper method to calculate average work hours
     */
    private function calculateAverageWorkHours($startDate, $endDate): float
    {
        return round(Attendance::betweenDates($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->avg('work_hours') ?? 0, 2);
    }

    /**
     * Helper method to calculate approval rate
     */
    private function calculateApprovalRate($startDate, $endDate): float
    {
        $totalAttendance = Attendance::betweenDates($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->count();
        
        $approvedAttendance = Attendance::betweenDates($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->approved()
            ->count();

        return $totalAttendance > 0 ? round(($approvedAttendance / $totalAttendance) * 100, 2) : 0;
    }
}