<?php

namespace App\Http\Controllers\APIs;

use Exception;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Personal;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);
            $page = $request->input('page', 1);
            $orderBy = $request->input('orderBy', 'employees.created_at');
            $sortedBy = $request->input('sortedBy', 'desc');
            $search = $request->input('search', '');
            $offset = ($page - 1) * $limit;

            $validOrderColumns = ['employees.created_at', 'employees.updated_at'];
            $validSortDirections = ['asc', 'desc'];

            $orderBy = in_array($orderBy, $validOrderColumns) ? $orderBy : 'employees.created_at';
            $sortedBy = in_array($sortedBy, $validSortDirections) ? $sortedBy : 'desc';

            $dataArray = Employee::join('personals','personals.id','=','employees.personal_id')
                                ->join('roles','roles.id','=','employees.role_id')
                                ->selectRaw('ROW_NUMBER() OVER(ORDER BY '.$orderBy.' '.$sortedBy.') as number,
                                employees.id,
                                personals.name,
                                employees.email,
                                personals.address,
                                personals.state,
                                personals.district,
                                personals.register_code,
                                employees.phonenumber,
                                employees.status,
                                roles.name as role
                                ')
                            ->when($search, function ($query, $search) {
                                $query->where(function ($query) use ($search) {
                                    $query->where('employees.department', 'like', "%{$search}%");
                                });
                            });

            $total = $dataArray->get()->count();

            $data = $dataArray
                ->orderBy($orderBy, $sortedBy)
                ->skip($offset)
                ->take($limit)
                ->get();

            return response()->json([
                "message" => "success",
                "total" => $total,
                "data" => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request)
    {
        try{
            DB::beginTransaction();

            $dob = Carbon::parse($request->input('dob'))->format('Y-m-d');
            $hireDate = Carbon::parse($request->input('hireDate'))->format('Y-m-d');

            //Check NRC is already exist in Personal Data
            $existingPersonal = Personal::where('state', $request->input('state'))
                ->where('district', $request->input('district'))
                ->where('register_code', $request->input('registerCode'))   
                ->first();

            if ($existingPersonal) {
                throw new Exception('A record with the same state, district, and register code already exists.');
            }

            $personal = Personal::create([
                'id' => Str::uuid(),  // Use UUID for personal record
                'name' => $request->input('name'),
                'gender' => $request->input('gender'),
                'dob' => $dob,
                'address' => $request->input('address'),
                'state' => $request->input('state'),
                'district' => $request->input('district'),
                'register_code' => (string)$request->input('registerCode'),
            ]);

            Employee::create([
                'id' => Str::uuid(),  // Use UUID for employee record
                'personal_id' => $personal->id,  // Associate with the personal record
                'email' => $request->input('email'),
                'phonenumber' => $request->input('phonenumber'),
                'password' => bcrypt($request->input('password')),  // Encrypt the password
                'role_id' => $request->input('role'),
                'department' => $request->input('department'),
                'salary' => (string)$request->input('salary'),
                'hire_date' => $hireDate,
                'status' => $request->input('status'),
            ]);

            DB::commit();

            return response()->json(['message' => 'Employee Data created successfully']);
        }catch (Exception $e) {
            DB::rollBack();

            Log::error('Error : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the employee data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $data = Employee::with(['personal', 'role'])->where('id', $id)->firstOrFail();
            $employee = new EmployeeResource($data);

            return response()->json($employee,200);
        }catch (ModelNotFoundException $e) {
            Log::error('Employee not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, string $id)
    {
        try {

            DB::beginTransaction();

            $dob = Carbon::parse($request->input('dob'))->format('Y-m-d');
            $hireDate = Carbon::parse($request->input('hireDate'))->format('Y-m-d');

            $employee = Employee::findOrFail($id);

            // Check if the NRC (state, district, register code) already exists in Personal Data
            $existingPersonal = Personal::where('id', '!=', $employee->personal_id)
                ->where('state', $request->input('state'))
                ->where('district', $request->input('district'))
                ->where('register_code', $request->input('registerCode'))
                ->first();

            if ($existingPersonal) {
                throw new Exception('A record with the same state, district, and register code already exists.');
            }

            $personal = Personal::findOrFail($employee->personal_id);

            $personal->update([
                'name' => $request->input('name'),
                'gender' => $request->input('gender'),
                'dob' => $dob,
                'address' => $request->input('address'),
                'state' => $request->input('state'),
                'district' => $request->input('district'),
                'register_code' => (string)$request->input('registerCode'),
            ]);

            $employee->update([
                'email' => $request->input('email'),
                'phonenumber' => $request->input('phonenumber'),
                'role_id' => $request->input('role'),
                'department' => $request->input('department'),
                'salary' => $request->input('salary'),
                'hire_date' => $hireDate,
                'status' => $request->input('status'),
            ]);

            DB::commit();

            return response()->json(['message' => 'Employee updated successfully.'], 200);
        }catch (Exception $e) {
            DB::rollBack();

            Log::error('Error updating employee', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($id);

            $employee->delete();

            DB::commit();

        return response()->json(['message' => 'Employee and their personal record deleted successfully.'], 200);

        }catch (Exception $e) {
            DB::rollBack();

            Log::error('Error updating employee', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
