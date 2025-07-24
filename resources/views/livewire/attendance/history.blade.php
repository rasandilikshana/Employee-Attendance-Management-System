<div class="max-w-7xl mx-auto px-6 sm:px-8 py-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">
            <flux:icon.calendar-days class="mr-2"/>
            Attendance History
        </flux:heading>
        
        <flux:button 
            wire:click="exportData"
            variant="outline"
            icon="arrow-down-tray"
        >
            Export CSV
        </flux:button>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $summaryStats['total_days'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Days</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($summaryStats['total_hours'], 1) }}h</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Hours</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-600">{{ $summaryStats['present_days'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Present Days</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold {{ $summaryStats['late_days'] > 0 ? 'text-orange-600' : 'text-green-600' }}">{{ $summaryStats['late_days'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Late Days</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $summaryStats['approved_records'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Approved</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ $summaryStats['pending_records'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Pending</div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <flux:card.header>
            <flux:heading size="lg">Filters</flux:heading>
        </flux:card.header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Date From -->
            <flux:field>
                <flux:label>From Date</flux:label>
                <flux:input 
                    type="date" 
                    wire:model.live="dateFrom"
                />
            </flux:field>

            <!-- Date To -->
            <flux:field>
                <flux:label>To Date</flux:label>
                <flux:input 
                    type="date" 
                    wire:model.live="dateTo"
                />
            </flux:field>

            <!-- Status Filter -->
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                    <flux:select.option value="">All Statuses</flux:select.option>
                    <flux:select.option value="present">Present</flux:select.option>
                    <flux:select.option value="late">Late</flux:select.option>
                    <flux:select.option value="absent">Absent</flux:select.option>
                    <flux:select.option value="partial">Partial</flux:select.option>
                </flux:select>
            </flux:field>

            <!-- Approval Filter -->
            <flux:field>
                <flux:label>Approval Status</flux:label>
                <flux:select wire:model.live="approvalFilter" placeholder="All Records">
                    <flux:select.option value="">All Records</flux:select.option>
                    <flux:select.option value="1">Approved</flux:select.option>
                    <flux:select.option value="0">Pending</flux:select.option>
                </flux:select>
            </flux:field>

            <!-- Clear Filters -->
            <flux:field>
                <flux:label>&nbsp;</flux:label>
                <flux:button 
                    wire:click="clearFilters"
                    variant="outline"
                    icon="x-mark"
                    class="w-full"
                >
                    Clear Filters
                </flux:button>
            </flux:field>
        </div>
    </flux:card>

    <!-- Attendance Records Table -->
    <flux:card>
        <flux:card.header>
            <div class="flex justify-between items-center">
                <flux:heading size="lg">Attendance Records</flux:heading>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $attendanceRecords->total() }} records found
                </div>
            </div>
        </flux:card.header>

        @if($attendanceRecords->count() > 0)
            <flux:table>
                <flux:columns>
                    <flux:column>Date</flux:column>
                    <flux:column>Day</flux:column>
                    <flux:column>Clock In</flux:column>
                    <flux:column>Clock Out</flux:column>
                    <flux:column>Work Hours</flux:column>
                    <flux:column>Break Duration</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>Approved</flux:column>
                    <flux:column>Notes</flux:column>
                </flux:columns>
                
                <flux:rows>
                    @foreach($attendanceRecords as $record)
                        <flux:row :key="$record->id">
                            <flux:cell>
                                <div class="font-medium">{{ $record->date->format('M j, Y') }}</div>
                                <div class="text-sm text-zinc-500">{{ $record->date->diffForHumans() }}</div>
                            </flux:cell>
                            
                            <flux:cell>
                                <div class="font-medium">{{ $record->date->format('l') }}</div>
                            </flux:cell>
                            
                            <flux:cell>
                                @if($record->check_in_time)
                                    <div class="font-medium {{ $record->status === 'late' ? 'text-orange-600' : 'text-green-600' }}">
                                        {{ $record->check_in_time->format('H:i:s') }}
                                    </div>
                                    @if($record->status === 'late')
                                        <flux:badge color="orange" size="sm">Late</flux:badge>
                                    @endif
                                @else
                                    <span class="text-zinc-400">--:--:--</span>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                @if($record->check_out_time)
                                    <span class="font-medium text-blue-600">
                                        {{ $record->check_out_time->format('H:i:s') }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">--:--:--</span>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                @if($record->work_hours)
                                    <div class="font-medium">
                                        {{ number_format(abs($record->work_hours), 1) }}h
                                    </div>
                                    @if($record->work_hours >= 8)
                                        <flux:badge color="green" size="sm">Full</flux:badge>
                                    @elseif($record->work_hours >= 4)
                                        <flux:badge color="blue" size="sm">Partial</flux:badge>
                                    @endif
                                @else
                                    <span class="text-zinc-400">--</span>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                @if($record->break_duration)
                                    <span class="text-sm">{{ $record->break_duration }} min</span>
                                @else
                                    <span class="text-zinc-400">--</span>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                @php
                                    $statusColors = [
                                        'present' => 'green',
                                        'late' => 'orange',
                                        'absent' => 'red',
                                        'partial' => 'blue'
                                    ];
                                @endphp
                                <flux:badge color="{{ $statusColors[$record->status] ?? 'zinc' }}">
                                    {{ ucfirst($record->status) }}
                                </flux:badge>
                            </flux:cell>
                            
                            <flux:cell>
                                @if($record->is_approved)
                                    <div class="flex items-center text-green-600">
                                        <flux:icon.check-circle class="mr-1" size="sm"/>
                                        <span class="text-sm">Approved</span>
                                    </div>
                                    @if($record->approved_at)
                                        <div class="text-xs text-zinc-500">
                                            {{ $record->approved_at->format('M j, H:i') }}
                                        </div>
                                    @endif
                                @else
                                    <div class="flex items-center text-orange-600">
                                        <flux:icon.clock class="mr-1" size="sm"/>
                                        <span class="text-sm">Pending</span>
                                    </div>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                @if($record->notes)
                                    <div class="max-w-xs">
                                        <div class="text-sm text-zinc-700 dark:text-zinc-300 truncate" title="{{ $record->notes }}">
                                            {{ $record->notes }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-zinc-400 text-sm">No notes</span>
                                @endif
                            </flux:cell>
                        </flux:row>
                    @endforeach
                </flux:rows>
            </flux:table>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $attendanceRecords->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <flux:icon.calendar-days class="mx-auto mb-4 text-zinc-400" size="3xl"/>
                <flux:heading size="lg" class="mb-2">No Records Found</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                    No attendance records match your current filters.
                </p>
                <div class="flex justify-center space-x-3">
                    <flux:button wire:click="clearFilters" variant="outline" icon="x-mark">
                        Clear Filters
                    </flux:button>
                    <flux:button href="{{ route('attendance.clock') }}" wire:navigate icon="clock">
                        Clock In/Out
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notification', (event) => {
            alert(event.message);
        });
    });
</script>
