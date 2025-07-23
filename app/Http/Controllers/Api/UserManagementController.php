<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{

    /**
     * Get all users with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|string|exists:roles,name',
            'status' => 'nullable|in:active,inactive',
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
            $query = User::with(['roles']);

            // Search by name or email
            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            // Filter by role
            if ($request->role) {
                $query->role($request->role);
            }

            $perPage = $request->input('per_page', 15);
            $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            // Assign role for API guard
            $role = \Spatie\Permission\Models\Role::where('name', $request->role)
                ->where('guard_name', 'api')
                ->first();

            if ($role) {
                $user->assignRole($role);
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        'created_at' => $user->created_at,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific user details
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::with(['roles', 'attendances' => function ($query) {
                $query->latest()->take(10);
            }])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Calculate user statistics
            $totalAttendance = Attendance::where('user_id', $user->id)->count();
            $approvedAttendance = Attendance::where('user_id', $user->id)->approved()->count();
            $pendingAttendance = Attendance::where('user_id', $user->id)->pendingApproval()->count();
            $totalWorkHours = Attendance::where('user_id', $user->id)->sum('work_hours');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ],
                    'statistics' => [
                        'total_attendance' => $totalAttendance,
                        'approved_attendance' => $approvedAttendance,
                        'pending_attendance' => $pendingAttendance,
                        'total_work_hours' => round($totalWorkHours, 2),
                    ],
                    'recent_attendance' => $user->attendances
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user information
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'role' => 'sometimes|required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['name', 'email']);

            if ($request->password) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            if ($request->role) {
                // Sync role for API guard
                $role = \Spatie\Permission\Models\Role::where('name', $request->role)
                    ->where('guard_name', 'api')
                    ->first();

                if ($role) {
                    $user->syncRoles([$role]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        'updated_at' => $user->updated_at,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Prevent deleting the current admin user
            $currentUser = request()->user();
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account',
                ], 403);
            }

            // Soft delete to preserve attendance records
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all attendance records for a specific user
     */
    public function getUserAttendance(Request $request, $userId): JsonResponse
    {
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
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $query = Attendance::where('user_id', $userId)
                ->with(['approver:id,name']);

            // Apply filters
            if ($request->start_date && $request->end_date) {
                $query->betweenDates($request->start_date, $request->end_date);
            }

            if ($request->status) {
                $query->byStatus($request->status);
            }

            $perPage = $request->input('per_page', 15);
            $attendance = $query->orderBy('date', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'attendance' => $attendance
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve attendance records
     */
    public function approveAttendance(Request $request): JsonResponse
    {
        // Check if user has admin role for API guard
        $user = $request->user();
        if (!$user->hasRole('admin', 'api')) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'integer|exists:attendances,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $currentUser = request()->user();
            $attendanceIds = $request->attendance_ids;

            $attendanceRecords = Attendance::whereIn('id', $attendanceIds)
                ->where('is_approved', false)
                ->get();

            if ($attendanceRecords->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending attendance records found',
                ], 404);
            }

            foreach ($attendanceRecords as $attendance) {
                $attendance->approve($currentUser->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance records approved successfully',
                'data' => [
                    'approved_count' => $attendanceRecords->count(),
                    'approved_by' => $currentUser->name,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Approval failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending attendance records for approval
     */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        // Check if user has admin role for API guard
        $user = $request->user();
        if (!$user->hasRole('admin', 'api')) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:users,id',
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
            $query = Attendance::pendingApproval()
                ->with(['user:id,name,email']);

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            $perPage = $request->input('per_page', 15);
            $pendingAttendance = $query->orderBy('date', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pendingAttendance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending approvals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available roles
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::all(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $roles
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
