<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number, 
            'name' => $this->employee->personal->name,
            'email' => $this->employee->email,
            'phonenumber' => $this->employee->phonenumber,
            'gender' => $this->employee->personal->gender,
            'dob' => $this->employee->personal->dob,
            'address' => $this->employee->personal->address,
            'state' => $this->employee->personal->state,
            'district' => $this->employee->personal->district,
            'registerCode' => $this->employee->personal->register_code,
            'salary' => $this->employee->salary,
            'hireDate' => $this->employee->hire_date,
            'teacher_id' => $this->teacher_id,
            'specialization' => $this->specialization,
            'designation' => $this->designation,
            'employment_type' => $this->employment_type,
            'status' => $this->employee->status
        ];
    }

}
