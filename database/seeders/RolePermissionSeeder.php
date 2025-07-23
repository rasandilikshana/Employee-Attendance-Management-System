<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Attendance permissions
            'mark-attendance',
            'view-own-attendance',
            'view-all-attendance',
            'manage-attendance',

            // User management permissions
            'manage-users',
            'view-users',

            // Report permissions
            'generate-reports',
            'export-reports',
        ];

        // Create permissions for both web and api guards
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create Admin role for web guard and assign permissions
        $adminRoleWeb = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRoleWeb->givePermissionTo([
            'view-all-attendance',
            'manage-attendance',
            'manage-users',
            'view-users',
            'generate-reports',
            'export-reports',
        ]);

        // Create Admin role for api guard and assign permissions
        $adminRoleApi = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRoleApi->givePermissionTo(
            Permission::where('guard_name', 'api')->whereIn('name', [
                'view-all-attendance',
                'manage-attendance',
                'manage-users',
                'view-users',
                'generate-reports',
                'export-reports',
            ])->get()
        );

        // Create Employee role for web guard and assign permissions
        $employeeRoleWeb = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employeeRoleWeb->givePermissionTo([
            'mark-attendance',
            'view-own-attendance',
        ]);

        // Create Employee role for api guard and assign permissions
        $employeeRoleApi = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'api']);
        $employeeRoleApi->givePermissionTo(
            Permission::where('guard_name', 'api')->whereIn('name', [
                'mark-attendance',
                'view-own-attendance',
            ])->get()
        );

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@attendance.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->hasRole('admin', 'web')) {
            $admin->assignRole('admin');  // web guard (default)
        }
        if (!$admin->hasRole('admin', 'api')) {
            $admin->assignRole(Role::where('name', 'admin')->where('guard_name', 'api')->first());
        }

        // Create default employee user
        $employee = User::firstOrCreate(
            ['email' => 'employee@attendance.com'],
            [
                'name' => 'Employee User',
                'password' => Hash::make('employee123'),
                'email_verified_at' => now(),
            ]
        );
        if (!$employee->hasRole('employee', 'web')) {
            $employee->assignRole('employee');  // web guard (default)
        }
        if (!$employee->hasRole('employee', 'api')) {
            $employee->assignRole(Role::where('name', 'employee')->where('guard_name', 'api')->first());
        }

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Admin: admin@attendance.com / admin123');
        $this->command->info('Employee: employee@attendance.com / employee123');
    }
}
