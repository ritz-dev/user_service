<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'address' => $this->address,
            'state' => $this->state,
            'district' => $this->district,
            'register_code' => $this->register_code,
        ];
    }
}
