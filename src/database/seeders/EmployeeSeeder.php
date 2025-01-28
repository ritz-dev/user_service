<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Employee;
use App\Models\Personal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::first();
        $personals = Personal::take(1)->get();

        Employee::create([
            'personal_id' => $personals[0]->id,
            "email" => "aye@gmail.com",
            "phonenumber" => "09799123123",
            "password" => Hash::make("Asd123!@#"),
            'role_id' => $role->id,
            'department' => 'Adminstrator',
            'salary' => 7000000,
            'hire_date' => '2024-12-09',
        ]);
    }
}
