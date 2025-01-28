<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
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

            $dataArray = Role::selectRaw('Row_Number() OVER(ORDER By '.$orderBy.' '.$sortedBy.') as number,id,name,description')
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
        try {

            DB::beginTransaction();

            $request->validate([
                "name" => "required|string",
                "permissions" => "required|array"
            ]);

            $roleName = $request->input('name');
            $roleDescription = $request->input('description');
            $permissionsArray = $request->input('permissions');

            $role = Role::firstOrCreate([
                'name' => $roleName,
                'description' => $roleDescription,
            ]);

            $permissionIds = collect($permissionsArray)->map(function ($permissionName) {

                $permission = Permission::where('name', $permissionName)->first();
                if(!$permission){
                    $permission = new Permission;
                    $permission->name = $permissionName;
                    $permission->save();
                    return $permission->id;
                }else{
                    return $permission->id;
                }
            })->filter()->all();

            $role->permissions()->attach($permissionIds);

            DB::commit();

            return response()->json(['message' => 'Role and permissions saved successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $role = Role::with('permissions')->where('id',$id)->firstOrFail();

            $transformedRole = [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];

            return response()->json($transformedRole,200);

        } catch (\Exception $e) {
            Log::error('Error Fetching role for edit: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the role data.',
                'error' => $e->getMessage(),
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
        try {
            DB::beginTransaction();

            $request->validate([
                "name" => "required|string",
                "permissions" => "required|array"
            ]);

            $role = Role::with('permissions')->where('id',$id)->firstOrFail();

            $role->name = $request->input('name');
            $role->description = $request->input('description');
            $role->save();

            $permissionsArray = $request->input('permissions');

            $permissionIds = collect($permissionsArray)->map(function ($permissionName) {

                $permission = Permission::where('name', $permissionName)->first();
                if(!$permission){
                    $permission = new Permission;
                    $permission->name = $permissionName;
                    $permission->save();
                    return $permission->id;
                }else{
                    return $permission->id;
                }
            })->filter()->all();
            $role->permissions()->sync($permissionIds);

            $transformedRole = [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];


            DB::commit();

            return response()->json($transformedRole, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the role.',
                'error' => $e->getMessage(),
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

            $role = Role::findOrFail($id);

            // Detach all permissions associated with the role
            $role->permissions()->detach();

            $role->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.',
            ], 200);

        }catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Role not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Role not found.',
            ], 404);
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
