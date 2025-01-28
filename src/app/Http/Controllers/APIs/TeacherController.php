<?php

namespace App\Http\Controllers\APIs;

use Exception;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Http\Requests\UpdateTeacherRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Personal;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Support\Facades\DB;


class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);
            $page = $request->input('page', 1);
            $orderBy = $request->input('orderBy', 'teachers.created_at');
            $sortedBy = $request->input('sortedBy', 'desc');
            $search = $request->input('search', '');
            $offset = ($page - 1) * $limit;

            $validOrderColumns = ['teachers.created_at', 'teachers.updated_at'];
            $validSortDirections = ['asc', 'desc'];

            $orderBy = in_array($orderBy, $validOrderColumns) ? $orderBy : 'teachers.created_at';
            $sortedBy = in_array($sortedBy, $validSortDirections) ? $sortedBy : 'desc';

            $dataArray = Teacher::join('employees','employees.id','=','teachers.employee_id')
                            ->join('roles','roles.id','=','employees.role_id')                
                            ->join('personals', 'personals.id', '=', 'employees.personal_id')
                            ->selectRaw('ROW_NUMBER() OVER(ORDER BY '.$orderBy.' '.$sortedBy.') as number,
                                teachers.id as id,
                                teachers.name,
                                employees.email,
                                teachers.address,
                                employees.phonenumber,
                                personals.state,
                                personals.district,
                                personals.register_code,
                                employees.status as status,
                                teachers.specialization as specialization,
                                teachers.designation as designation,
                                roles.name as role
                            ')
                            ->when($search, function ($query, $search) {
                                $query->where(function ($query) use ($search) {
                                    $query->where('teachers.teacher_id', 'like', "%{$search}%");
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
    public function store(TeacherRequest $request)
    {
        try{
            DB::beginTransaction();

            $dob = Carbon::parse($request->input('dob'))->format('Y-m-d');
            $hireDate = Carbon::parse($request->input('hireDate'))->format('Y-m-d');

            $teacher_role = Role::where('name', 'Teacher')->firstOrFail();

            if ($teacher_role) {
                throw new Exception('Teacher role is not exist.');
            }
            
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

            $employee = Employee::create([
                'id' => Str::uuid(),  // Use UUID for employee record
                'personal_id' => $personal->id,  // Associate with the personal record
                'email' => $request->input('email'),
                'phonenumber' => $request->input('phonenumber'),
                'password' => bcrypt($request->input('password')),  // Encrypt the password
                'role_id' => $teacher_role->id,
                'department' => 'department',
                'salary' => $request->input('salary'),
                'hire_date' => $hireDate,
                'status' => $request->input('status'),
            ]);

            $teacher = Teacher::create([
                'id' => Str::uuid(),  // Use UUID for employee record
                'employee_id' => $employee->id,  // Associate with the personal record
                'teacher_code' => Str::uuid(),
                'name'=> $request->input('name'),
                'address' => $request->input('address'),
                'specialization' => $request->input('specialization'),
                'designation' => $request->input('designation'),  // Encrypt the password
            ]);

            DB::commit();

            return response()->json(['message' => 'Teacher created successfully']);
        }catch (Exception $e) {

            Log::error('Error : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the teacher.',
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
            $data = Teacher::with(['employee.personal'])->findOrFail($id);
            logger($data);
            $teacher = new TeacherResource($data);

            return response()->json($teacher,200);
        }catch (ModelNotFoundException $e) {
            Log::error('Teacher not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Teacher not found.',
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
    public function update(UpdateTeacherRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            $dob = Carbon::parse($request->input('dob'))->format('Y-m-d');
            $hireDate = Carbon::parse($request->input('hireDate'))->format('Y-m-d');

            $teacher_role = Role::firstOrFail();

            $teacher = Teacher::findOrFail($id);
            $employee = Employee::findOrFail($teacher->employee_id);
            $personal = Personal::findOrFail($employee->personal_id);
            //Check NRC is already exist in Personal Data
            $existingPersonal = Personal::where('state', $request->input('state'))
                ->where('district', $request->input('district'))
                ->where('register_code', $request->input('registerCode'))   
                ->first();

            if ($existingPersonal) {
                throw new Exception('A record with the same state, district, and register code already exists.');
            }

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
                'password' => $employee->password,  // Encrypt the password
                'role_id' => $teacher_role,
                'department' => 'department',
                'salary' => (string)$request->input('salary'),
                'hire_date' => $hireDate,
                'status' => $request->input('status'),
            ]);

            $teacher->update([
                'specialization' => $request->input('specialization'),
                'designation' => $request->input('designation'),  // Encrypt the password
            ]);

            DB::commit();

        }catch (Exception $e) {
            Log::error('Error updating teacher', ['error' => $e->getMessage()]);

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
            $teacher = Teacher::findOrFail($id);
            $employee = Employee::findOrFail($teacher->employee_id);
            $personal = Personal::findOrFail($employee->personal_id);
            $teacher->delete();
            $employee->delete();
            $personal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully.',
            ]);
        }catch (ModelNotFoundException $e) {
            Log::error('Teacher not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Teacher not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting teacher: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting teacher.',
            ], 500);
        }
    }
}
