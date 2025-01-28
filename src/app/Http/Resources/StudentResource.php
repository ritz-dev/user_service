<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phonenumber' => $this->phonenumber,
            'address' => $this->address,
            'state' => $this->personal->state,
            'district' => $this->personal->district,
            'registerCode' => $this->personal->register_code,
            'dob' => $this->personal->dob,
            'gender' => $this->personal->gender,
            'pob' => $this->pob,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'blood_type' => $this->blood_type,
            'academic_level' => $this->academic_level,
            'academic_year' => $this->academic_year,
            'image_url' => $this->image_url,
            'enrollment_date' => $this->enrollment_date,
            'graduation_date' => $this->graduation_date,
            'status' => $this->status,
            'parent_info' => $this->parentInfos->map(function ($parentInfo){
                return [
                    'id' => $parentInfo->id,
                    'name' => $parentInfo->name,
                    'email' => $parentInfo->email,
                    'phonenumber' => $parentInfo->phonenumber,
                    'title' => $parentInfo->title,
                    'state' => $parentInfo->personal->state,
                    'district' => $parentInfo->personal->district,
                    'registerCode' => $parentInfo->personal->register_code,
                ];
            })
        ];
    }
}
