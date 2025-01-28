<?php

namespace App\Http\Controllers\APIs;

use Exception;
use App\Models\Student;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Http\Resources\StudentResource;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\ParentInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;


class StudentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);
            $page = $request->input('page', 1);
            $orderBy = $request->input('orderBy', 'students.created_at');
            $sortedBy = $request->input('sortedBy', 'desc');
            $search = $request->input('search', '');
            $offset = ($page - 1) * $limit;

            $validOrderColumns = ['students.created_at', 'students.updated_at'];
            $validSortDirections = ['asc', 'desc'];

            $orderBy = in_array($orderBy, $validOrderColumns) ? $orderBy : 'students.created_at';
            $sortedBy = in_array($sortedBy, $validSortDirections) ? $sortedBy : 'desc';

            $dataArray = Student::join('personals','personals.id','=','students.personal_id')
                                ->selectRaw('ROW_NUMBER() OVER(ORDER BY '.$orderBy.' '.$sortedBy.') as number,
                                personals.gender,
                                personals.dob,
                                personals.state,
                                personals.district,
                                personals.register_code as registerCode,
                                students.id,
                                students.name,
                                students.student_code as code,
                                students.email,
                                students.phonenumber,
                                students.status,
                                students.academic_year
                            ')
                            ->when($search, function ($query, $search) {
                                $query->where(function ($query) use ($search) {
                                    $query->where('students.student_id', 'like', "%{$search}%");
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
    public function store(StudentRequest $request)
    {
        try{
            DB::beginTransaction();
           
            $dob = Carbon::parse($request->input('dob'))->format('Y-m-d');
            $enrollment_date = Carbon::parse($request->input('enrollment_date'))->format('Y-m-d');

            // $existingPersonal = Personal::where('state', $request->input('state'))
            //     ->where('district', $request->input('district'))
            //     ->where('register_code', $request->input('registerCode'))   
            //     ->first();

            $personal = Personal::create([
                'id' => Str::uuid(),
                'name' => $request->input('name'),
                'gender' => $request->input('gender'),
                'dob' => $dob,
                'address' => $request->input('address'),
                'state' => $request->input('state'),
                'district' => $request->input('district'),
                'register_code' => (string)$request->input('registerCode'),
            ]);

            $student = Student::create([
                'id' => Str::uuid(),
                'personal_id' => $personal->id,
                'name' => $request->input('name'),
                'student_code' => $personal->state. '/' . $personal->district . '(N)' . $personal->registor_code,
                'address' => $request->input('address'),
                'email' => $request->input('email'),
                'phonenumber' => $request->input('phonenumber'),
                'pob' => $request->input('pob'),
                'nationality' =>$request->input('nationality'),
                'religion' =>$request->input('religion'),
                'blood_type' =>$request->input('blood_type'),
                'status' =>$request->input('status'),
                'academic_level' =>$request->input('academic_level'),
                'academic_year' =>$request->input('academic_year'),
                'enrollment_date' =>$enrollment_date,
                'graduation_date' =>null,
            ]);

            $parentInfos = $request->input('parent_info'); // Assuming 'parent_info' is an array of parent details

            foreach ($parentInfos as $parentInfo) {

                // Check if the personal record exists
                $existingPersonal = Personal::where('state', $parentInfo['state'])
                                    ->where('district', $parentInfo['district'])
                                    ->where('register_code', $parentInfo['registerCode'])
                                    ->first();

                if (!$existingPersonal) {
                    $existingPersonal = Personal::create([
                        'id' => Str::uuid(),
                        'name' => $parentInfo['name'],
                        'gender' => $parentInfo['title'] === 'Mother' ? 'female' : ( $parentInfo['title'] === 'Father' ? 'male' : $parentInfo['title']),
                        'dob' => $dob,
                        'address' => 'nullable',
                        'state' => $parentInfo['state'],
                        'district' => $parentInfo['district'],
                        'register_code' => (string)$parentInfo['registerCode'],
                    ]);
                }

                ParentInfo::create([
                    'id' => Str::uuid(),
                    'personal_id' => $existingPersonal->id,
                    'student_id' => $student->id,
                    'name' => $parentInfo['name'],
                    'email' => $parentInfo['email'],
                    'phonenumber' => $parentInfo['phonenumber'],
                    'title' => $parentInfo['title'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Student created successfully']);
        }catch (Exception $e) {

            DB::rollBack();

            Log::error('Error : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the student.',
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
            $data = Student::with(['personal','parentInfos.personal'])->where('id',$id)->findOrFail($id);
            $student = new StudentResource($data);

            return response()->json($student,200);
        }catch (ModelNotFoundException $e) {
            Log::error('Student not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
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
    public function update(UpdateStudentRequest $request, string $id)
    {
        try {
            DB::beginTransaction();
           
            $dob = Carbon::parse($request->input('dob'))->format('Y-m-d');
            $enrollment_date = Carbon::parse($request->input('enrollment_date'))->format('Y-m-d');

            $student = Student::findOrFail($id);
            // $existingPersonal = Personal::where('state', $request->input('state'))
            //     ->where('district', $request->input('district'))
            //     ->where('register_code', $request->input('registerCode'))   
            //     ->first();

            $existingPersonal = Personal::where('id', '!=', $student->personal_id)
                ->where('state', $request->input('state'))
                ->where('district', $request->input('district'))
                ->where('register_code', $request->input('registerCode'))
                ->first();

            if ($existingPersonal) {
                throw new Exception('A record with the same state, district, and register code already exists.');
            }

            $personal = Personal::findOrFail($student->personal_id);

            $personal->update([
                'name' => $request->input('name'),
                'gender' => $request->input('gender'),
                'dob' => $dob,
                'address' => $request->input('address'),
                'state' => $request->input('state'),
                'district' => $request->input('district'),
                'register_code' => (string)$request->input('registerCode'),
            ]);

            $student->update([
                'name' => $request->input('name'),
                'code' => $personal->state. '/' . $personal->district . '(N)' . $personal->register_code,
                'address' => $request->input('address'),
                'email' => $request->input('email'),
                'phonenumber' => $request->input('phonenumber'),
                'pob' => $request->input('pob'),
                'nationality' =>$request->input('nationality'),
                'religion' =>$request->input('religion'),
                'blood_type' =>$request->input('blood_type'),
                'status' =>$request->input('status'),
                'academic_level' =>$request->input('academic_level'),
                'academic_year' =>$request->input('academic_year'),
                'enrollment_date' =>$enrollment_date,
                'graduation_date' =>null,
            ]);

            $parentInfos = $request->input('parent_info'); // Assuming 'parent_info' is an array of parent details

            foreach ($parentInfos as $parentInfo) {

                $parent = ParentInfo::findOrFail($parentInfo['id']);

                // Check if the personal record exists
                $existingPersonal = Personal::where('state', $parentInfo['state'])
                                    ->where('district', $parentInfo['district'])
                                    ->where('register_code', $parentInfo['registerCode'])
                                    ->first();

                if (!$existingPersonal) {
                    $existingPersonal = Personal::create([
                        'id' => Str::uuid(),
                        'name' => $parentInfo['name'],
                        'gender' => $parentInfo['title'] === 'Mother' ? 'female' : 'male',
                        'dob' => $dob,
                        'address' => 'nullable',
                        'state' => $parentInfo['state'],
                        'district' => $parentInfo['district'],
                        'register_code' => (string)$parentInfo['registerCode'],
                    ]);
                }

                $parent->update([
                    'personal_id' => $existingPersonal->id,
                    'student_id' => $student->id,
                    'name' => $parentInfo['name'],
                    'email' => $parentInfo['email'],
                    'phonenumber' => $parentInfo['phonenumber'],
                    'title' => $parentInfo['title'],
                ]);
            }

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => 'Student updated successfully.',
            ],200);


        }catch (Exception $e) {

            DB::rollBack();

            Log::error('Error updating student', ['error' => $e->getMessage()]);

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
            $student = Student::where('id',$id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully.',
            ]);
        }catch (ModelNotFoundException $e) {
            Log::error('Student not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting student: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting student.',
            ], 500);
        }
    }
}
