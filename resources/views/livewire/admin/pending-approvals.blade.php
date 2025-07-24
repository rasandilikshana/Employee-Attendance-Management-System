<div class="max-w-7xl mx-auto px-6 sm:px-8 py-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">
            <flux:icon.check-circle class="mr-2"/>
            Pending Approvals
        </flux:heading>
        
        <div class="flex items-center space-x-4">
            <!-- User Filter -->
            <div class="w-64">
                <flux:select wire:model.live="userFilter" placeholder="Filter by employee">
                    <flux:select.option value="">All Employees</flux:select.option>
                    @foreach($users as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            
            <!-- Bulk Approve Button -->
            <flux:button 
                wire:click="approveSelected"
                variant="primary"
                icon="check"
                :disabled="empty($selectedRecords)"
            >
                Approve Selected ({{ count($selectedRecords) }})
            </flux:button>
        </div>
    </div>

    <!-- Statistics Cards -->
    @php
        $totalPending = \App\Models\Attendance::where('is_approved', false)->count();
        $todayPending = \App\Models\Attendance::where('is_approved', false)
            ->whereDate('date', today())->count();
        $totalEmployees = \App\Models\User::role('employee')->count();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-orange-600">{{ $totalPending }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Pending</div>
                </div>
                <flux:icon.clock class="text-orange-500" size="xl"/>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-red-600">{{ $todayPending }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Today's Pending</div>
                </div>
                <flux:icon.exclamation-triangle class="text-red-500" size="xl"/>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-blue-600">{{ $totalEmployees }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Employees</div>
                </div>
                <flux:icon.users class="text-blue-500" size="xl"/>
            </div>
        </flux:card>
    </div>

    <!-- Pending Records Table -->
    <flux:card>
        <flux:card.header>
            <div class="flex justify-between items-center">
                <flux:heading size="lg">Attendance Records Awaiting Approval</flux:heading>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $pendingRecords->total() }} records found
                </div>
            </div>
        </flux:card.header>

        @if($pendingRecords->count() > 0)
            <flux:table>
                <flux:columns>
                    <flux:column>
                        <flux:checkbox 
                            wire:model.live="selectAll"
                            :indeterminate="count($selectedRecords) > 0 && count($selectedRecords) < $pendingRecords->count()"
                        />
                    </flux:column>
                    <flux:column>Employee</flux:column>
                    <flux:column>Date</flux:column>
                    <flux:column>Clock In</flux:column>
                    <flux:column>Clock Out</flux:column>
                    <flux:column>Hours</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>Notes</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>
                
                <flux:rows>
                    @foreach($pendingRecords as $record)
                        <flux:row :key="$record->id">
                            <flux:cell>
                                <flux:checkbox 
                                    wire:model.live="selectedRecords" 
                                    value="{{ $record->id }}"
                                />
                            </flux:cell>
                            
                            <flux:cell>
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                                        <span class="text-sm font-semibold">
                                            {{ $record->user->name[0] ?? '?' }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $record->user->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $record->user->email }}</div>
                                    </div>
                                </div>
                            </flux:cell>
                            
                            <flux:cell>
                                <div class="font-medium">{{ $record->date->format('M j, Y') }}</div>
                                <div class="text-sm text-zinc-500">{{ $record->date->format('l') }}</div>
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
                                    <span class="font-medium">
                                        {{ number_format(abs($record->work_hours), 1) }}h
                                    </span>
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
                                @if($record->notes)
                                    <div class="max-w-xs truncate" title="{{ $record->notes }}">
                                        {{ $record->notes }}
                                    </div>
                                @else
                                    <span class="text-zinc-400">No notes</span>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                <div class="flex items-center space-x-2">
                                    <flux:button 
                                        wire:click="approveRecord({{ $record->id }})"
                                        variant="subtle" 
                                        size="sm"
                                        icon="check"
                                    >
                                        Approve
                                    </flux:button>
                                </div>
                            </flux:cell>
                        </flux:row>
                    @endforeach
                </flux:rows>
            </flux:table>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $pendingRecords->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <flux:icon.check-circle class="mx-auto mb-4 text-green-500" size="3xl"/>
                <flux:heading size="lg" class="mb-2">All Caught Up!</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                    There are no pending attendance records to approve.
                </p>
                <flux:button href="{{ route('admin.reports') }}" wire:navigate icon="chart-bar">
                    View Reports
                </flux:button>
            </div>
        @endif
    </flux:card>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notification', (event) => {
            // Handle notifications here - you can integrate with your preferred notification library
            alert(event.message);
        });
    });
</script>