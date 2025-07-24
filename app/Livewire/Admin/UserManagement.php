<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

#[Layout('components.layouts.app')]
class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingUser = null;

    // Form properties
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = '';
    public $is_active = true;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? '';
        $this->is_active = $user->is_active ?? true;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingUser = null;
        $this->resetForm();
    }

    public function createUser()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'is_active' => $this->is_active,
            'email_verified_at' => now()
        ]);

        $user->assignRole($this->role);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'User created successfully.'
        ]);

        $this->closeCreateModal();
        $this->resetPage();
    }

    public function updateUser()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUser->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|exists:roles,name'
        ]);

        $this->editingUser->update([
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active
        ]);

        if ($this->password) {
            $this->editingUser->update([
                'password' => Hash::make($this->password)
            ]);
        }

        $this->editingUser->syncRoles([$this->role]);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'User updated successfully.'
        ]);

        $this->closeEditModal();
    }

    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);
        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        $this->dispatch('notification', [
            'type' => 'success',
            'message' => "User {$status} successfully."
        ]);
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You cannot delete your own account.'
            ]);
            return;
        }

        $user->delete();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'User deleted successfully.'
        ]);

        $this->resetPage();
    }

    public function resetForm()
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role', 'is_active']);
        $this->is_active = true;
    }

    public function render()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
            $query->role($this->roleFilter);
        }

        $users = $query->with('roles')->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::all();

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'admins' => User::role('admin')->count(),
            'employees' => User::role('employee')->count(),
        ];

        return view('livewire.admin.user-management', [
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats
        ]);
    }
}
