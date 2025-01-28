<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'name' => $this->personal->name,
            'email' => $this->email,
            'phonenumber' => $this->phonenumber,
            'role' => (string)$this->role->id,
            'gender' => $this->personal->gender,
            'dob' => $this->personal->dob,
            'address' => $this->personal->address,
            'state' => $this->personal->state,
            'district' => $this->personal->district,
            'registerCode' => $this->personal->register_code,
            'department' => $this->department,
            'salary' => $this->salary,
            'hireDate' => $this->hire_date,
            'status' => $this->status,
            'employment_type' => $this->employment_type
        ];
    }
}
