<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PermissionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{

            $limit = $request->input('limit',15);
            $page = $request->input('page',1);
            $orderBy = $request->input('orderBy','created_at');
            $sortedBy = $request->input('sortedBy','desc');
            $search = $request->input('search','');
            $offset = ($page-1) * $limit;

            $validOrderColumns = ['create_at','updated_at'];
            $validSortDirections = ['asc','desc'];

            $orderBy = in_array($orderBy,$validOrderColumns) ? $orderBy : 'created_at';
            $sortedBy = in_array($sortedBy,$validSortDirections) ? $sortedBy : 'desc';

            $dataArray = Permission::selectRaw('Row_Number() OVER(ORDER By '.$orderBy.' '.$sortedBy.') as number,id,name')
                            ->when($search,function($query,$search){
                                return $query->where('name','LIKE', "%$search%");
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

        }catch(Exception $e){
            Log::error('Error fetching: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving.',
            ], 500);
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
    public function store(Request $request)
    {
        try{
            $request->validate([
                "name" => "required|string",
            ]);

            $permission = new Permission;
            $permission->name = $request->name;
            $permission->save();

            Log::info('Created successfully', ['permission_id' => $permission->id]);

            return response()->json([
                'success' => true,
                'message' => "Creating Successfully.",
            ]);
        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $data = Permission::findOrFail($id);
            $permission = new PermissionResource($data);
            return response()->json($permission,200);
        }catch (ModelNotFoundException $e) {
            Log::error('Permission not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Permission not found.',
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
    public function update(Request $request, string $id)
    {
        try{

        $request->validate([
            "name" => "required|string",
        ]);
            $permission = Permission::findOrFail($id);
            $permission->name = $request->name;
            $permission->save();

            Log::info('Updated successfully', ['permission_id' => $permission->id]);

            return response()->json([
                'success' => true,
                'message' => "Updating Successfully.",
                'data' => $permission,
            ]);
        }catch (ModelNotFoundException $e) {
            Log::error('Permission not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Permission not found.',
            ], 404);
        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $permission = Permission::findOrFail($id);

            $permission->delete();
            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully.',
            ]);
        }catch (ModelNotFoundException $e) {
            Log::error('Permission not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Permission not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting.',
            ], 500);
        }
    }
}
