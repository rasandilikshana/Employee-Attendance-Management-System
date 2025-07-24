<div class="max-w-7xl mx-auto px-6 sm:px-8 py-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">
            <flux:icon.chart-bar class="mr-2"/>
            Reports & Analytics
        </flux:heading>
        
        <flux:button 
            wire:click="exportReport"
            variant="primary"
            icon="arrow-down-tray"
        >
            Export {{ ucfirst($reportType) }} Report
        </flux:button>
    </div>

    <!-- Overall Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $overallStats['total_employees'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Employees</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($overallStats['total_attendance_records']) }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Attendance Records</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ $overallStats['pending_approvals'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Pending Approvals</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ number_format($overallStats['total_work_hours'], 1) }}h</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Work Hours</div>
            </div>
        </flux:card>
    </div>

    <!-- Filters and Report Type -->
    <flux:card class="mb-6">
        <flux:card.header>
            <flux:heading size="lg">Filter & Generate Reports</flux:heading>
        </flux:card.header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

            <!-- Employee Filter -->
            <flux:field>
                <flux:label>Employee</flux:label>
                <flux:select wire:model.live="employeeFilter" placeholder="All Employees">
                    <flux:select.option value="">All Employees</flux:select.option>
                    @foreach($employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <!-- Report Type -->
            <flux:field>
                <flux:label>Report Type</flux:label>
                <flux:select wire:model.live="reportType">
                    <flux:select.option value="summary">Summary Report</flux:select.option>
                    <flux:select.option value="detailed">Detailed Report</flux:select.option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Monthly Trends -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">Monthly Trends (Last 6 Months)</flux:heading>
            </flux:card.header>

            <div class="space-y-4">
                @foreach($monthlyTrends as $trend)
                    <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <div class="font-medium">{{ $trend['month'] }}</div>
                        <div class="flex space-x-4 text-sm">
                            <span class="text-blue-600">{{ $trend['records'] }} records</span>
                            <span class="text-green-600">{{ number_format($trend['hours'], 1) }}h</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <!-- Top Performers -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">Top Performers (By Hours)</flux:heading>
            </flux:card.header>

            <div class="space-y-3">
                @forelse($topPerformers as $index => $performer)
                    <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                <span class="text-sm font-bold text-blue-600">{{ $index + 1 }}</span>
                            </div>
                            <div>
                                <div class="font-medium">{{ $performer['user']->name }}</div>
                                <div class="text-sm text-zinc-500">{{ $performer['present_days'] }} days present</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-semibold text-green-600">{{ number_format($performer['total_hours'], 1) }}h</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-zinc-500">
                        No performance data available for selected period.
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>

    <!-- Late Arrivals Summary -->
    @if($lateArrivals->count() > 0)
        <flux:card class="mb-6">
            <flux:card.header>
                <flux:heading size="lg">Late Arrivals Summary</flux:heading>
            </flux:card.header>

            <flux:table>
                <flux:columns>
                    <flux:column>Employee</flux:column>
                    <flux:column>Late Count</flux:column>
                    <flux:column>Latest Late Record</flux:column>
                </flux:columns>
                
                <flux:rows>
                    @foreach($lateArrivals as $late)
                        <flux:row>
                            <flux:cell>
                                <div class="font-medium">{{ $late['user']->name }}</div>
                            </flux:cell>
                            <flux:cell>
                                <flux:badge color="orange">{{ $late['late_count'] }} times</flux:badge>
                            </flux:cell>
                            <flux:cell>
                                <div class="text-sm">
                                    {{ $late['latest_record']->date->format('M j, Y') }}
                                    <span class="text-zinc-500">
                                        ({{ $late['latest_record']->check_in_time->format('H:i') }})
                                    </span>
                                </div>
                            </flux:cell>
                        </flux:row>
                    @endforeach
                </flux:rows>
            </flux:table>
        </flux:card>
    @endif

    <!-- Report Data -->
    <flux:card>
        <flux:card.header>
            <flux:heading size="lg">
                {{ ucfirst($reportType) }} Report
                @if($employeeFilter)
                    - {{ $employees->find($employeeFilter)->name ?? '' }}
                @endif
            </flux:heading>
        </flux:card.header>

        @if($reportType === 'summary')
            <!-- Summary Report Table -->
            @if(count($reportData) > 0)
                <flux:table>
                    <flux:columns>
                        <flux:column>Employee</flux:column>
                        <flux:column>Total Days</flux:column>
                        <flux:column>Present Days</flux:column>
                        <flux:column>Late Days</flux:column>
                        <flux:column>Total Hours</flux:column>
                        <flux:column>Approved</flux:column>
                        <flux:column>Pending</flux:column>
                    </flux:columns>
                    
                    <flux:rows>
                        @foreach($reportData as $data)
                            <flux:row>
                                <flux:cell>
                                    <div>
                                        <div class="font-medium">{{ $data['user']->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $data['user']->email }}</div>
                                    </div>
                                </flux:cell>
                                <flux:cell>
                                    <span class="font-medium">{{ $data['total_days'] }}</span>
                                </flux:cell>
                                <flux:cell>
                                    <span class="text-green-600 font-medium">{{ $data['present_days'] }}</span>
                                </flux:cell>
                                <flux:cell>
                                    @if($data['late_days'] > 0)
                                        <flux:badge color="orange">{{ $data['late_days'] }}</flux:badge>
                                    @else
                                        <span class="text-green-600">0</span>
                                    @endif
                                </flux:cell>
                                <flux:cell>
                                    <span class="font-medium">{{ number_format($data['total_hours'], 1) }}h</span>
                                </flux:cell>
                                <flux:cell>
                                    <flux:badge color="green">{{ $data['approved_records'] }}</flux:badge>
                                </flux:cell>
                                <flux:cell>
                                    @if($data['pending_records'] > 0)
                                        <flux:badge color="orange">{{ $data['pending_records'] }}</flux:badge>
                                    @else
                                        <span class="text-green-600">0</span>
                                    @endif
                                </flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
            @else
                <div class="text-center py-8">
                    <flux:icon.chart-bar class="mx-auto mb-4 text-zinc-400" size="3xl"/>
                    <flux:heading size="lg" class="mb-2">No Data Available</flux:heading>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        No attendance data found for the selected filters.
                    </p>
                </div>
            @endif
        @else
            <!-- Detailed Report Table -->
            @if($reportData->count() > 0)
                <div class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                    Showing first 50 records. Export for complete data.
                </div>
                
                <flux:table>
                    <flux:columns>
                        <flux:column>Employee</flux:column>
                        <flux:column>Date</flux:column>
                        <flux:column>Clock In</flux:column>
                        <flux:column>Clock Out</flux:column>
                        <flux:column>Hours</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Approved</flux:column>
                    </flux:columns>
                    
                    <flux:rows>
                        @foreach($reportData as $record)
                            <flux:row>
                                <flux:cell>
                                    <div>
                                        <div class="font-medium">{{ $record->user->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $record->user->email }}</div>
                                    </div>
                                </flux:cell>
                                <flux:cell>
                                    <div class="font-medium">{{ $record->date->format('M j, Y') }}</div>
                                    <div class="text-sm text-zinc-500">{{ $record->date->format('l') }}</div>
                                </flux:cell>
                                <flux:cell>
                                    @if($record->check_in_time)
                                        <span class="font-medium {{ $record->status === 'late' ? 'text-orange-600' : 'text-green-600' }}">
                                            {{ $record->check_in_time->format('H:i:s') }}
                                        </span>
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
                                        <span class="font-medium">{{ number_format(abs($record->work_hours), 1) }}h</span>
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
                                        <flux:badge color="green">Yes</flux:badge>
                                    @else
                                        <flux:badge color="orange">Pending</flux:badge>
                                    @endif
                                </flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
            @else
                <div class="text-center py-8">
                    <flux:icon.calendar-days class="mx-auto mb-4 text-zinc-400" size="3xl"/>
                    <flux:heading size="lg" class="mb-2">No Records Found</flux:heading>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        No attendance records found for the selected filters.
                    </p>
                </div>
            @endif
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
