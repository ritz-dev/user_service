<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            "name" => "Admin",
            "description" => 'Administrator with full access to the system.',
        ]);

        Role::create([
            "name" => "HR",
            "description" => 'Human Resource.',
        ]);

        Role::create([
            "name" => "Teacher",
            "description" => 'Teacher authorize access.',
        ]);
    }
}
