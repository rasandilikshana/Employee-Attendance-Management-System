<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'notes',
        'work_hours',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime:H:i:s',
        'check_out_time' => 'datetime:H:i:s',
        'work_hours' => 'decimal:2',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_LATE = 'late';

    /**
     * Get all available statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_ABSENT,
            self::STATUS_PARTIAL,
            self::STATUS_LATE,
        ];
    }

    /**
     * Get the user that owns the attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved this attendance.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate work hours between check-in and check-out
     *
     * @return float|null
     */
    public function calculateWorkHours(): ?float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        return round($checkOut->diffInMinutes($checkIn) / 60, 2);
    }

    /**
     * Automatically calculate and save work hours
     */
    public function updateWorkHours(): void
    {
        $this->work_hours = $this->calculateWorkHours();
        $this->save();
    }

    /**
     * Check if attendance is late (after 9 AM)
     *
     * @return bool
     */
    public function isLate(): bool
    {
        if (!$this->check_in_time) {
            return false;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $lateThreshold = Carbon::parse('09:00:00');

        return $checkIn->isAfter($lateThreshold);
    }

    /**
     * Mark attendance as approved
     *
     * @param int $approvedBy
     * @return void
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'is_approved' => true,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Scope for getting attendance by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for getting attendance by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for getting approved attendance
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for getting pending approval attendance
     */
    public function scopePendingApproval($query)
    {
        return $query->where('is_approved', false);
    }
}