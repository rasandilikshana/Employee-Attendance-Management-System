<x-layouts.app title="Dashboard">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 py-6">
        <flux:heading size="xl" class="mb-6">
            Welcome back, {{ auth()->user()->name }}!
        </flux:heading>

        @php
            $today = \Carbon\Carbon::today();
            $todayAttendance = \App\Models\Attendance::where('user_id', auth()->id())
                ->where('date', $today)
                ->first();
                
            $weeklyStats = \App\Models\Attendance::where('user_id', auth()->id())
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->get();
        @endphp

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Clock Status -->
            <flux:card class="relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Today's Status</div>
                        @if($todayAttendance)
                            @if($todayAttendance->check_out_time)
                                <flux:badge color="blue">Clocked Out</flux:badge>
                            @elseif($todayAttendance->check_in_time)
                                <flux:badge color="green">Clocked In</flux:badge>
                            @else
                                <flux:badge color="zinc">Not Started</flux:badge>
                            @endif
                        @else
                            <flux:badge color="zinc">Not Started</flux:badge>
                        @endif
                    </div>
                    <flux:icon.clock class="text-blue-500" size="xl"/>
                </div>
                <flux:button 
                    href="{{ route('attendance.clock') }}" 
                    wire:navigate 
                    variant="ghost" 
                    size="sm" 
                    icon="clock"
                    class="mt-3 w-full"
                >
                    Go to Clock In/Out
                </flux:button>
            </flux:card>

            <!-- Weekly Hours -->
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ number_format(abs($weeklyStats->sum('work_hours')), 1) }}h
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">This Week</div>
                    </div>
                    <flux:icon.chart-bar class="text-green-500" size="xl"/>
                </div>
            </flux:card>

            <!-- Present Days -->
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ $weeklyStats->where('status', '!=', 'absent')->count() }}/5
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Days Present</div>
                    </div>
                    <flux:icon.calendar-days class="text-blue-500" size="xl"/>
                </div>
            </flux:card>

            <!-- Late Days -->
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold {{ $weeklyStats->where('status', 'late')->count() > 0 ? 'text-orange-600' : 'text-green-600' }}">
                            {{ $weeklyStats->where('status', 'late')->count() }}
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Late Days</div>
                    </div>
                    <flux:icon.exclamation-triangle class="{{ $weeklyStats->where('status', 'late')->count() > 0 ? 'text-orange-500' : 'text-green-500' }}" size="xl"/>
                </div>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Today's Details -->
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">Today's Attendance</flux:heading>
                </flux:card.header>
                
                @if($todayAttendance)
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Clock In</div>
                                <div class="text-lg font-semibold {{ $todayAttendance->status === 'late' ? 'text-orange-600' : 'text-green-600' }}">
                                    {{ $todayAttendance->check_in_time ? $todayAttendance->check_in_time->format('H:i') : '--:--' }}
                                </div>
                            </div>
                            <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Clock Out</div>
                                <div class="text-lg font-semibold text-blue-600">
                                    {{ $todayAttendance->check_out_time ? $todayAttendance->check_out_time->format('H:i') : '--:--' }}
                                </div>
                            </div>
                        </div>
                        
                        @if($todayAttendance->work_hours)
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">
                                    {{ number_format(abs($todayAttendance->work_hours), 1) }} hours
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">Total work time</div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-8">
                        <flux:icon.clock class="mx-auto mb-3 text-zinc-400" size="xl"/>
                        <p class="text-zinc-600 dark:text-zinc-400">No attendance record for today</p>
                        <flux:button 
                            href="{{ route('attendance.clock') }}" 
                            wire:navigate 
                            variant="primary" 
                            size="sm" 
                            icon="clock"
                            class="mt-3"
                        >
                            Clock In Now
                        </flux:button>
                    </div>
                @endif
            </flux:card>

            <!-- Quick Actions & Admin Panel -->
            <div class="space-y-6">
                <!-- Quick Navigation -->
                <flux:card>
                    <flux:card.header>
                        <flux:heading size="lg">Quick Actions</flux:heading>
                    </flux:card.header>
                    
                    <div class="space-y-3">
                        <flux:button 
                            href="{{ route('attendance.clock') }}" 
                            wire:navigate 
                            variant="outline" 
                            icon="clock"
                            class="w-full justify-start"
                        >
                            Clock In/Out
                        </flux:button>
                        
                        <flux:button 
                            href="{{ route('attendance.history') }}" 
                            wire:navigate 
                            variant="outline" 
                            icon="calendar-days"
                            class="w-full justify-start"
                        >
                            View My Attendance
                        </flux:button>
                        
                        <flux:button 
                            href="{{ route('settings.profile') }}" 
                            wire:navigate 
                            variant="outline" 
                            icon="cog"
                            class="w-full justify-start"
                        >
                            Settings
                        </flux:button>
                    </div>
                </flux:card>

                <!-- Admin Panel (if admin) -->
                @hasrole('admin')
                    <flux:card>
                        <flux:card.header>
                            <flux:heading size="lg">Admin Panel</flux:heading>
                        </flux:card.header>
                        
                        @php
                            $pendingCount = \App\Models\Attendance::where('is_approved', false)->count();
                            $totalUsers = \App\Models\User::role('employee')->count();
                        @endphp
                        
                        <div class="space-y-3">
                            <flux:button 
                                href="{{ route('admin.users') }}" 
                                wire:navigate 
                                variant="outline" 
                                icon="users"
                                class="w-full justify-between"
                            >
                                <span>User Management</span>
                                <flux:badge>{{ $totalUsers }}</flux:badge>
                            </flux:button>
                            
                            <flux:button 
                                href="{{ route('admin.approvals') }}" 
                                wire:navigate 
                                variant="outline" 
                                icon="check-circle"
                                class="w-full justify-between"
                            >
                                <span>Pending Approvals</span>
                                @if($pendingCount > 0)
                                    <flux:badge color="orange">{{ $pendingCount }}</flux:badge>
                                @else
                                    <flux:badge color="green">0</flux:badge>
                                @endif
                            </flux:button>
                            
                            <flux:button 
                                href="{{ route('admin.reports') }}" 
                                wire:navigate 
                                variant="outline" 
                                icon="chart-bar"
                                class="w-full justify-start"
                            >
                                Reports & Analytics
                            </flux:button>
                        </div>
                    </flux:card>
                @endhasrole
            </div>
        </div>

        <!-- Recent Activity -->
        @php
            $recentActivity = \App\Models\Attendance::where('user_id', auth()->id())
                ->with(['user:id,name'])
                ->latest('date')
                ->take(5)
                ->get();
        @endphp

        @if($recentActivity->count() > 0)
            <flux:card class="mt-8">
                <flux:card.header>
                    <div class="flex justify-between items-center">
                        <flux:heading size="lg">Recent Activity</flux:heading>
                        <flux:button 
                            href="{{ route('attendance.history') }}" 
                            wire:navigate 
                            variant="ghost" 
                            size="sm"
                            icon:trailing="arrow-right"
                        >
                            View All
                        </flux:button>
                    </div>
                </flux:card.header>
                
                <flux:table>
                    <flux:columns>
                        <flux:column>Date</flux:column>
                        <flux:column>Clock In</flux:column>  
                        <flux:column>Clock Out</flux:column>
                        <flux:column>Hours</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Approved</flux:column>
                    </flux:columns>
                    
                    <flux:rows>
                        @foreach($recentActivity as $record)
                            <flux:row>
                                <flux:cell>{{ $record->date->format('M j, Y') }}</flux:cell>
                                <flux:cell>{{ $record->check_in_time ? $record->check_in_time->format('H:i') : '--' }}</flux:cell>
                                <flux:cell>{{ $record->check_out_time ? $record->check_out_time->format('H:i') : '--' }}</flux:cell>
                                <flux:cell>{{ $record->work_hours ? number_format(abs($record->work_hours), 1) . 'h' : '--' }}</flux:cell>
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
            </flux:card>
        @endif
    </div>
</x-layouts.app>