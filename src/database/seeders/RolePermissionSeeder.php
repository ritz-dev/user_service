<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all roles and permissions
        $roles = Role::get();
        $permissions = Permission::get()->pluck('id')->toArray();

        // Prepare an array to hold the role permission data
        $pivotData = [];

        // Loop through each role
        foreach($roles as $role) {
            // For each role, loop through all permissions
            foreach($permissions as $permission) {
                // Add the role permission data to the pivot array
                $pivotData[] = [
                    'role_id' => $role->id,
                    'permission_id' => $permission,
                ];
            }
        }

        // Insert all the data at once
        RolePermission::insert($pivotData);
    }
}
