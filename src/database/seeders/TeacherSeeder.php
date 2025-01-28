<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\Employee;
use App\Models\Personal;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $role = Role::skip(2)->take(1)->first();
        
        // Ensure a valid role was retrieved
        if (!$role) {
            throw new \Exception("Role not found at the specified index.");
        }

        // Retrieve a single personal instance
        $personal = Personal::skip(1)->take(1)->first();

        // Ensure a valid personal record was retrieved
        if (!$personal) {
            throw new \Exception("Personal record not found at the specified index.");
        }

        $employee = Employee::create([
            'id' => Str::uuid(), 
            'personal_id' => $personal->id,
            "email" => "ayeaye@gmail.com",
            "phonenumber" => "09799123163",
            "password" => Hash::make("Asd123!@#"),
            'role_id' => $role->id,
            'department' => 'Academic',
            'salary' => 7000000,
            'hire_date' => '2024-12-09',
            'employment_type' => 'full-time',
        ]);

        Teacher::create([
            'id' => Str::uuid(),
            'employee_id' => $employee->id,
            'teacher_code' => 'TT001',
            'name' => $personal->name,
            'address' => $personal->address,
            'specialization' => 'English',
            'designation' => 'English',
        ]);
    }
}
