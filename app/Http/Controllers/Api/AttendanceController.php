<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Clock in - Record attendance check-in
     */
    public function clockIn(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $today = Carbon::today();

        // Check if user already clocked in today
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'You have already clocked in today',
                'data' => [
                    'check_in_time' => $existingAttendance->check_in_time,
                    'status' => $existingAttendance->status,
                ]
            ], 400);
        }

        try {
            $checkInTime = now();
            $isLate = $checkInTime->format('H:i:s') > '09:00:00';
            
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $today,
                ],
                [
                    'check_in_time' => $checkInTime,
                    'status' => $isLate ? Attendance::STATUS_LATE : Attendance::STATUS_PRESENT,
                    'notes' => $request->input('notes', ''),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $isLate ? 'Clocked in successfully (Late)' : 'Clocked in successfully',
                'data' => [
                    'id' => $attendance->id,
                    'check_in_time' => $attendance->check_in_time,
                    'status' => $attendance->status,
                    'is_late' => $isLate,
                    'date' => $attendance->date,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Clock in failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clock out - Record attendance check-out
     */
    public function clockOut(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $today = Carbon::today();

        // Find today's attendance record
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'You must clock in first before clocking out',
            ], 400);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'You have already clocked out today',
                'data' => [
                    'check_out_time' => $attendance->check_out_time,
                    'work_hours' => $attendance->work_hours,
                ]
            ], 400);
        }

        try {
            $checkOutTime = now();
            $attendance->update([
                'check_out_time' => $checkOutTime,
                'notes' => $request->input('notes', $attendance->notes),
            ]);

            // Calculate work hours
            $attendance->updateWorkHours();

            return response()->json([
                'success' => true,
                'message' => 'Clocked out successfully',
                'data' => [
                    'id' => $attendance->id,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'work_hours' => $attendance->work_hours,
                    'status' => $attendance->status,
                    'date' => $attendance->date,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Clock out failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's attendance history
     */
    public function getMyAttendance(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:' . implode(',', Attendance::getStatuses()),
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Attendance::where('user_id', $user->id)
                ->with(['approver:id,name']);

            // Apply date filters
            if ($request->start_date && $request->end_date) {
                $query->betweenDates($request->start_date, $request->end_date);
            } elseif ($request->start_date) {
                $query->where('date', '>=', $request->start_date);
            } elseif ($request->end_date) {
                $query->where('date', '<=', $request->end_date);
            }

            // Apply status filter
            if ($request->status) {
                $query->byStatus($request->status);
            }

            $perPage = $request->input('per_page', 15);
            $attendance = $query->orderBy('date', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $attendance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's attendance status
     */
    public function getTodayStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $today = Carbon::today();
            $attendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            $data = [
                'date' => $today->format('Y-m-d'),
                'has_clocked_in' => false,
                'has_clocked_out' => false,
                'check_in_time' => null,
                'check_out_time' => null,
                'work_hours' => 0,
                'status' => null,
                'is_approved' => false,
            ];

            if ($attendance) {
                $data = [
                    'date' => $attendance->date,
                    'has_clocked_in' => !is_null($attendance->check_in_time),
                    'has_clocked_out' => !is_null($attendance->check_out_time),
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'work_hours' => $attendance->work_hours ?? 0,
                    'status' => $attendance->status,
                    'is_approved' => $attendance->is_approved,
                    'notes' => $attendance->notes,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get today\'s status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update attendance record (for corrections)
     */
    public function updateAttendance(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'check_in_time' => 'nullable|date_format:H:i:s',
            'check_out_time' => 'nullable|date_format:H:i:s|after:check_in_time',
            'notes' => 'nullable|string|max:500',
            'status' => 'nullable|in:' . implode(',', Attendance::getStatuses()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attendance = Attendance::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record not found',
                ], 404);
            }

            // Only allow updates if not approved yet
            if ($attendance->is_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update approved attendance record',
                ], 403);
            }

            $updateData = array_filter($request->only(['check_in_time', 'check_out_time', 'notes', 'status']));
            
            $attendance->update($updateData);

            // Recalculate work hours if times were updated
            if (isset($updateData['check_in_time']) || isset($updateData['check_out_time'])) {
                $attendance->updateWorkHours();
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => $attendance->refresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}