<div class="max-w-7xl mx-auto px-6 sm:px-8 py-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">
            <flux:icon.users class="mr-2"/>
            User Management
        </flux:heading>
        
        <flux:button 
            wire:click="openCreateModal"
            variant="primary"
            icon="plus"
        >
            Add New User
        </flux:button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['total_users'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Users</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['active_users'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Active</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-red-600">{{ $stats['inactive_users'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Inactive</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $stats['admins'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Admins</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ $stats['employees'] }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Employees</div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <flux:card.header>
            <flux:heading size="lg">Filters</flux:heading>
        </flux:card.header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <flux:field>
                <flux:label>Search Users</flux:label>
                <flux:input 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or email..."
                />
            </flux:field>

            <!-- Role Filter -->
            <flux:field>
                <flux:label>Filter by Role</flux:label>
                <flux:select wire:model.live="roleFilter" placeholder="All Roles">
                    <flux:select.option value="">All Roles</flux:select.option>
                    @foreach($roles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ ucfirst($role->name) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <!-- Clear Filters -->
            <flux:field>
                <flux:label>&nbsp;</flux:label>
                <flux:button 
                    wire:click="$set('search', ''); $set('roleFilter', '')"
                    variant="outline"
                    icon="x-mark"
                    class="w-full"
                >
                    Clear Filters
                </flux:button>
            </flux:field>
        </div>
    </flux:card>

    <!-- Users Table -->
    <flux:card>
        <flux:card.header>
            <div class="flex justify-between items-center">
                <flux:heading size="lg">Users</flux:heading>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $users->total() }} users found
                </div>
            </div>
        </flux:card.header>

        @if($users->count() > 0)
            <flux:table>
                <flux:columns>
                    <flux:column>User</flux:column>
                    <flux:column>Role</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>Joined</flux:column>
                    <flux:column>Last Activity</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>
                
                <flux:rows>
                    @foreach($users as $user)
                        <flux:row :key="$user->id">
                            <flux:cell>
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                                        <span class="text-sm font-semibold">
                                            {{ $user->name[0] ?? '?' }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $user->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </flux:cell>
                            
                            <flux:cell>
                                @if($user->roles->count() > 0)
                                    @foreach($user->roles as $role)
                                        <flux:badge 
                                            color="{{ $role->name === 'admin' ? 'purple' : 'blue' }}"
                                            class="mr-1"
                                        >
                                            {{ ucfirst($role->name) }}
                                        </flux:badge>
                                    @endforeach
                                @else
                                    <flux:badge color="zinc">No Role</flux:badge>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                @if($user->is_active ?? true)
                                    <div class="flex items-center text-green-600">
                                        <flux:icon.check-circle class="mr-1" size="sm"/>
                                        <span class="text-sm">Active</span>
                                    </div>
                                @else
                                    <div class="flex items-center text-red-600">
                                        <flux:icon.x-circle class="mr-1" size="sm"/>
                                        <span class="text-sm">Inactive</span>
                                    </div>
                                @endif
                            </flux:cell>
                            
                            <flux:cell>
                                <div class="text-sm">
                                    <div class="font-medium">{{ $user->created_at->format('M j, Y') }}</div>
                                    <div class="text-zinc-500">{{ $user->created_at->diffForHumans() }}</div>
                                </div>
                            </flux:cell>
                            
                            <flux:cell>
                                <div class="text-sm text-zinc-500">
                                    @if($user->last_login_at)
                                        {{ $user->last_login_at->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </div>
                            </flux:cell>
                            
                            <flux:cell>
                                <div class="flex items-center space-x-2">
                                    <!-- Edit Button -->
                                    <flux:button 
                                        wire:click="openEditModal({{ $user->id }})"
                                        variant="subtle" 
                                        size="sm"
                                        icon="pencil"
                                    />
                                    
                                    <!-- Toggle Status -->
                                    <flux:button 
                                        wire:click="toggleUserStatus({{ $user->id }})"
                                        variant="subtle" 
                                        size="sm"
                                        :icon="($user->is_active ?? true) ? 'eye-slash' : 'eye'"
                                    />
                                    
                                    <!-- Delete Button (only if not current user) -->
                                    @if($user->id !== auth()->id())
                                        <flux:button 
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="Are you sure you want to delete this user? This action cannot be undone."
                                            variant="subtle" 
                                            size="sm"
                                            icon="trash"
                                        />
                                    @endif
                                </div>
                            </flux:cell>
                        </flux:row>
                    @endforeach
                </flux:rows>
            </flux:table>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <flux:icon.users class="mx-auto mb-4 text-zinc-400" size="3xl"/>
                <flux:heading size="lg" class="mb-2">No Users Found</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                    @if($search || $roleFilter)
                        No users match your current filters.
                    @else
                        Get started by creating your first user.
                    @endif
                </p>
                <flux:button wire:click="openCreateModal" icon="plus">
                    Add New User
                </flux:button>
            </div>
        @endif
    </flux:card>

    <!-- Create User Modal -->
    @if($showCreateModal)
        <flux:modal name="create-user">
            <form wire:submit="createUser">
                <flux:modal.header>
                    <flux:heading size="lg">Create New User</flux:heading>
                </flux:modal.header>

                <flux:modal.body class="space-y-6">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="name" required />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="email" required />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Password</flux:label>
                        <flux:input type="password" wire:model="password" required />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Confirm Password</flux:label>
                        <flux:input type="password" wire:model="password_confirmation" required />
                        <flux:error name="password_confirmation" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Role</flux:label>
                        <flux:select wire:model="role" placeholder="Select a role" required>
                            @foreach($roles as $roleOption)
                                <flux:select.option value="{{ $roleOption->name }}">{{ ucfirst($roleOption->name) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="role" />
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="is_active">
                            Active (user can log in)
                        </flux:checkbox>
                    </flux:field>
                </flux:modal.body>

                <flux:modal.footer>
                    <div class="flex space-x-3">
                        <flux:button type="button" wire:click="closeCreateModal" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            Create User
                        </flux:button>
                    </div>
                </flux:modal.footer>
            </form>
        </flux:modal>
    @endif

    <!-- Edit User Modal -->
    @if($showEditModal && $editingUser)
        <flux:modal name="edit-user">
            <form wire:submit="updateUser">
                <flux:modal.header>
                    <flux:heading size="lg">Edit User</flux:heading>
                </flux:modal.header>

                <flux:modal.body class="space-y-6">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="name" required />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="email" required />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>New Password (leave blank to keep current)</flux:label>
                        <flux:input type="password" wire:model="password" />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Confirm New Password</flux:label>
                        <flux:input type="password" wire:model="password_confirmation" />
                        <flux:error name="password_confirmation" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Role</flux:label>
                        <flux:select wire:model="role" placeholder="Select a role" required>
                            @foreach($roles as $roleOption)
                                <flux:select.option value="{{ $roleOption->name }}">{{ ucfirst($roleOption->name) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="role" />
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="is_active">
                            Active (user can log in)
                        </flux:checkbox>
                    </flux:field>
                </flux:modal.body>

                <flux:modal.footer>
                    <div class="flex space-x-3">
                        <flux:button type="button" wire:click="closeEditModal" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            Update User
                        </flux:button>
                    </div>
                </flux:modal.footer>
            </form>
        </flux:modal>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notification', (event) => {
            alert(event.message);
        });
    });
</script>
