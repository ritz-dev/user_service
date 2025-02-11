<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // public function register(Request $request){

    //     $registeredData = $request->validate([
    //         'name' => 'required|string',
    //         'email' => 'email|required|string|unique:employees',
    //         'password' => 'required|confirmed'
    //     ]);

    //     $user = Employee::create([
    //         "name" => $request->name,
    //         "email" => $request->email,
    //         "password" => Hash::make($request->password),
    //         "role_id" => $request->role_id
    //     ]);

    //     $token = $user->createToken('passportToken')->accessToken;

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Successfully",
    //         "token" => $token,
    //         "data" => []
    //     ]);
    // }

    public function login(Request $request)
    {
        $credentials = [
            'email'    => $request->email,
            'password' => $request->password
        ];

        $employee = Employee::where('email', $request->email)->first();
        if ($employee && Hash::check($request->password, $employee->password)) {
            $token = $employee->createToken('SMS')->accessToken;


            $role = Role::where('id',$employee->role_id)->pluck('name')->first();
            $role_permissions = RolePermission::where('role_id',$employee->role_id)->get();

            $permission = [];

            foreach($role_permissions as $role_permission){
                $permission [] = Permission::where('id',$role_permission->permission_id)->pluck('name')->first();
            }
            return response()->json([
                'status' => 'success',
                'token' => $token,
                'token_type' => 'bearer',
                'admin' => $employee,
                'permissions' => $permission,
                'role' => $role
            ]);
        }

        // If no user or admin matches, return an error
        return response()->json(['error' => 'Unauthorized'], 401);

    }

    public function logout(){
        $user = auth()->guard('employee')->user();

        if ($user) {
            // Revoke the user's token if Passport is used
            $user->tokens->each(function ($token) {
                $token->delete();
            });

            return response()->json([
                'message' => "Logout Successfully",
            ]);
        }

        return response()->json([
            'message' => "User not logged in",
        ]);
    }

    public function me (Request $request) {

        $data = [
                'id' => $request->id,
                'name' => $request->name,
                'email' => $request->email,
            ];

        if(!$data) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($data);

        // $user = auth()->guard('employee')->user();

        // $name = $user->personal->name;

        // $role = Role::where('id',$user->role_id)->pluck('name')->first();

        // $role_permissions = RolePermission::where('role_id',$user->role_id)->get();

        // $permissionIds = [];

        // foreach($role_permissions as $role_permission){
        //     $permissionIds[] = $role_permission->permission_id;
        // }

        // $permission = [];

        // foreach($permissionIds as $permissionId){
        //     $permission[] = Permission::where('id',$permissionId)->pluck('name')->first();
        // }

        // $data = [
        //     'id' => $user->id,
        //     'name' => $name,
        //     'email' => $user->email,
        //     'role' => $role,
        //     'permissions' => $permission,
        // ];

        // if(!$data) {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        // return response()->json($data);
    }
}
