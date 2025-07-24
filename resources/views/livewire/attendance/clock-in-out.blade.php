<div class="max-w-4xl px-6 py-6 mx-auto sm:px-8">
    <!-- Page Header -->
    <flux:heading size="xl" class="mb-6">
        Clock In/Out
    </flux:heading>

    <!-- Current Time Display -->
    <flux:card class="mb-6">
        <div class="py-8 text-center">
            <div class="mb-2 font-mono text-4xl text-zinc-900 dark:text-white"
                 x-data="{ time: '{{ $currentTime }}' }"
                 x-init="setInterval(() => time = new Date().toLocaleString(), 1000)"
                 x-text="time">
            </div>
            <div class="text-lg text-zinc-600 dark:text-zinc-400">
                {{ now()->format('l, F j, Y') }}
            </div>
        </div>
    </flux:card>

    <div class="grid gap-6 md:grid-cols-2">
        <!-- Clock In/Out Actions -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">Today's Attendance</flux:heading>
            </flux:card.header>

            <div class="space-y-6">
                @if($todayAttendance)
                    <!-- Status Display -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 text-center rounded-lg bg-zinc-50 dark:bg-zinc-800">
                            <div class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Clock In</div>
                            <div class="text-lg font-semibold {{ $todayAttendance->status === 'late' ? 'text-orange-600' : 'text-green-600' }}">
                                {{ $todayAttendance->check_in_time ? $todayAttendance->check_in_time->format('H:i:s') : '--:--:--' }}
                            </div>
                            @if($todayAttendance->status === 'late')
                                <flux:badge color="orange" size="sm">Late</flux:badge>
                            @endif
                        </div>
                        <div class="p-4 text-center rounded-lg bg-zinc-50 dark:bg-zinc-800">
                            <div class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Clock Out</div>
                            <div class="text-lg font-semibold text-blue-600">
                                {{ $todayAttendance->check_out_time ? $todayAttendance->check_out_time->format('H:i:s') : '--:--:--' }}
                            </div>
                            @if($todayAttendance->work_hours)
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ abs($todayAttendance->work_hours) }} hours
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="flex justify-center">
                        @php
                            $statusColors = [
                                'present' => 'green',
                                'late' => 'orange',
                                'absent' => 'red',
                                'partial' => 'blue'
                            ];
                        @endphp
                        <flux:badge color="{{ $statusColors[$todayAttendance->status] ?? 'zinc' }}">
                            {{ ucfirst($todayAttendance->status) }}
                        </flux:badge>
                    </div>
                @else
                    <div class="p-8 text-center text-zinc-500 dark:text-zinc-400">
                        <flux:icon.clock class="mx-auto mb-3" variant="outline" size="xl"/>
                        <p>No attendance record for today</p>
                    </div>
                @endif

                <!-- Notes Field -->
                <flux:field>
                    <flux:label>Notes (Optional)</flux:label>
                    <flux:textarea
                        wire:model="notes"
                        placeholder="Add any notes about your attendance..."
                        rows="3"
                    />
                </flux:field>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-4">
                    <flux:button
                        wire:click="clockIn"
                        variant="primary"
                        size="base"
                        icon="play"
                        :disabled="$todayAttendance && $todayAttendance->check_in_time"
                        class="w-full"
                    >
                        Clock In
                    </flux:button>

                    <flux:button
                        wire:click="clockOut"
                        variant="danger"
                        size="base"
                        icon="stop"
                        :disabled="!$todayAttendance || !$todayAttendance->check_in_time || $todayAttendance->check_out_time"
                        class="w-full"
                    >
                        Clock Out
                    </flux:button>
                </div>
            </div>
        </flux:card>

        <!-- Quick Stats -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">This Week</flux:heading>
            </flux:card.header>

            @php
                $weeklyStats = \App\Models\Attendance::where('user_id', auth()->id())
                    ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->get();
                $totalHours = $weeklyStats->sum('work_hours');
                $presentDays = $weeklyStats->where('status', '!=', 'absent')->count();
                $lateDays = $weeklyStats->where('status', 'late')->count();
            @endphp

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <span class="text-zinc-600 dark:text-zinc-400">Total Hours</span>
                    <span class="text-lg font-semibold">{{ number_format(abs($totalHours), 1) }}h</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <span class="text-zinc-600 dark:text-zinc-400">Present Days</span>
                    <span class="text-lg font-semibold">{{ $presentDays }}/5</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <span class="text-zinc-600 dark:text-zinc-400">Late Days</span>
                    <span class="font-semibold text-lg {{ $lateDays > 0 ? 'text-orange-600' : '' }}">{{ $lateDays }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <span class="text-zinc-600 dark:text-zinc-400">Approval Status</span>
                    @if($todayAttendance && $todayAttendance->is_approved)
                        <flux:badge color="green">Approved</flux:badge>
                    @else
                        <flux:badge color="orange">Pending</flux:badge>
                    @endif
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Recent Activity -->
    @php
        $recentAttendance = \App\Models\Attendance::where('user_id', auth()->id())
            ->latest('date')
            ->take(5)
            ->get();
    @endphp

    @if($recentAttendance->count() > 0)
        <flux:card class="mt-6">
            <flux:card.header>
                <flux:heading size="lg">Recent Activity</flux:heading>
            </flux:card.header>

            <flux:table>
                <flux:columns>
                    <flux:column>Date</flux:column>
                    <flux:column>Clock In</flux:column>
                    <flux:column>Clock Out</flux:column>
                    <flux:column>Hours</flux:column>
                    <flux:column>Status</flux:column>
                </flux:columns>

                <flux:rows>
                    @foreach($recentAttendance as $record)
                        <flux:row>
                            <flux:cell>{{ $record->date->format('M j, Y') }}</flux:cell>
                            <flux:cell>{{ $record->check_in_time ? $record->check_in_time->format('H:i') : '--' }}</flux:cell>
                            <flux:cell>{{ $record->check_out_time ? $record->check_out_time->format('H:i') : '--' }}</flux:cell>
                            <flux:cell>{{ $record->work_hours ? number_format(abs($record->work_hours), 1) . 'h' : '--' }}</flux:cell>
                            <flux:cell>
                                <flux:badge color="{{ $statusColors[$record->status] ?? 'zinc' }}">
                                    {{ ucfirst($record->status) }}
                                </flux:badge>
                            </flux:cell>
                        </flux:row>
                    @endforeach
                </flux:rows>
            </flux:table>
        </flux:card>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notification', (event) => {
            // Handle notifications here
            alert(event.message);
        });
    });

    // Auto-refresh every minute
    setInterval(() => {
        @this.call('loadTodayAttendance');
    }, 60000);
</script>
