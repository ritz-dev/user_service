<?php

namespace Database\Seeders;

use App\Models\Personal;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;


class PersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Personal::create([
            'id' => Str::uuid(),  // Use UUID for personal record
            'name' => 'Aung',
            'gender' => 'male',
            'dob' => '1994-05-22',
            'address' => "Ygn",
            'state' => "12",
            'district' => "THAGAKA",
            'register_code' => "174505",
        ]);

        Personal::create([
            'id' => Str::uuid(),  // Use UUID for personal record
            'name' => 'Kyaw',
            'gender' => 'male',
            'dob' => '1997-05-22',
            'address' => "Ygn",
            'state' => "12",
            'district' => "THAGAKA",
            'register_code' => "145567",
        ]);

        Personal::create([
            'id' => Str::uuid(),  // Use UUID for personal record
            'name' => 'Thu',
            'gender' => 'male',
            'dob' => '2000-05-22',
            'address' => "Ygn",
            'state' => "12",
            'district' => "THAGAKA",
            'register_code' => "135564",
        ]);

    }
}
