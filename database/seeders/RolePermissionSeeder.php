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

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin role and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view-all-attendance',
            'manage-attendance',
            'manage-users',
            'view-users',
            'generate-reports',
            'export-reports',
        ]);

        // Create Employee role and assign permissions
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeeRole->givePermissionTo([
            'mark-attendance',
            'view-own-attendance',
        ]);

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@attendance.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
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
        if (!$employee->hasRole('employee')) {
            $employee->assignRole('employee');
        }

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Admin: admin@attendance.com / admin123');
        $this->command->info('Employee: employee@attendance.com / employee123');
    }
}
