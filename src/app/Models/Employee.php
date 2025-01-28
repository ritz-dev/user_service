<?php

namespace App\Models;

use App\Models\Personal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasApiTokens,Notifiable,HasFactory,HasUuids,SoftDeletes;

    protected $table = "employees";
    protected $fillable = [
        "personal_id",
        "email",
        "phonenumber",
        "password",
        "role_id",
        "department",
        "salary",
        "hire_date",
        "status",
        "employment_type"
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function personal(){
        return $this->belongsTo(Personal::class,'personal_id','id');
    }

    public function role(){
        return $this->belongsTo(Role::class,'role_id');
    }
}
