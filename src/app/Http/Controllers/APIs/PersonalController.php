<?php

namespace App\Http\Controllers\APIs;

use Exception;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalRequest;
use App\Http\Resources\PersonalResource;
use App\Http\Requests\UpdatePersonalRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PersonalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);
            $page = $request->input('page', 1);
            $orderBy = $request->input('orderBy', 'created_at');
            $sortedBy = $request->input('sortedBy', 'desc');
            $search = $request->input('search', '');
            $offset = ($page - 1) * $limit;

            $validOrderColumns = ['created_at', 'updated_at'];
            $validSortDirections = ['asc', 'desc'];

            $orderBy = in_array($orderBy, $validOrderColumns) ? $orderBy : 'created_at';
            $sortedBy = in_array($sortedBy, $validSortDirections) ? $sortedBy : 'desc';

            $dataArray = Personal::selectRaw('ROW_NUMBER() OVER(ORDER BY '.$orderBy.' '.$sortedBy.') as number,
                                name,
                                email,
                                gender,
                                dob,
                                state,
                                district,
                                register_code')
                            ->when($search, function ($query, $search) {
                                $query->where(function ($query) use ($search) {
                                    $query->where('first_name', 'like', "%{$search}%");
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
    public function store(PersonalRequest $request)
    {
        try{
            $personal = new Personal;
            $personal->name = $request->name;
            $personal->gender = $request->gender;
            $personal->dob = $request->dob;
            $personal->address = $request->address;
            $personal->state = $request->state;
            $personal->district = $request->district;
            $personal->register_code = $request->register_code;
            $personal->save();

            return response()->json(['message' => 'Personal Data created successfully']);
        }catch (Exception $e) {

            Log::error('Error : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the personal data.',
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
            $data = Personal::findOrFail($id);
            $personal = new PersonalResource($data);

            return response()->json($personal,200);
        }catch (ModelNotFoundException $e) {
            Log::error('Personal not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Personal not found.',
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
    public function update(UpdatePersonalRequest $request, string $id)
    {
        try {
            $personal = Personal::find($id);
            $personal->name = $request->name;
            $personal->gender = $request->gender;
            $personal->dob = $request->dob;
            $personal->address = $request->address;
            $personal->state = $request->state;
            $personal->district = $request->district;
            $personal->register_code = $request->register_code;
            $personal->save();

            return response()->json([
                "success" => true,
                "message" => 'Personal data updated successfully.',
            ],200);


        }catch (Exception $e) {
            Log::error('Error updating personal data', ['error' => $e->getMessage()]);

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
            $personal = Personal::where('id',$id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Personal data deleted successfully.',
            ]);
        }catch (ModelNotFoundException $e) {
            Log::error('Personal data not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Personal data not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting personal data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting personal data.',
            ], 500);
        }
    }
}
