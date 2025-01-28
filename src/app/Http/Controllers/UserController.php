<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);
            $page = $request->input('page', 1);
            $orderBy = $request->input('orderBy', 'users.created_at');
            $sortedBy = $request->input('sortedBy', 'desc');
            $search = $request->input('search', '');
            $offset = ($page - 1) * $limit;

            $validOrderColumns = ['users.created_at', 'users.updated_at'];
            $validSortDirections = ['asc', 'desc'];

            $orderBy = in_array($orderBy, $validOrderColumns) ? $orderBy : 'users.created_at';
            $sortedBy = in_array($sortedBy, $validSortDirections) ? $sortedBy : 'desc';

            $dataArray = User::join('roles','roles.id','=','users.role_id')
                                ->selectRaw('ROW_NUMBER() OVER(ORDER BY '.$orderBy.' '.$sortedBy.') as number,
                                users.name,
                                users.email,
                                roles.name as rolename')
                            ->when($search, function ($query, $search) {
                                $query->where(function ($query) use ($search) {
                                    $query->where('users.name', 'like', "%{$search}%");
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return response()->json(['message' => 'User created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $data = User::findOrFail($id);
            $user = new UserResource($data);

            return response()->json($user,200);
        }catch (ModelNotFoundException $e) {
            Log::error('User not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'User not found.',
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
        try {
            $user = User::where('id',$id)->first();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'email' => ['required','string','email','max:100',Rule::unique('users')->ignore($user->id)],
                'password' => 'nullable|string|min:8',
                'role_id' => 'required|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()]);
            }


            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            Log::info('User updated successfully', ['user_id' => $user->id]);

            return response()->json([
                "success" => true,
                "message" => 'User updated successfully.',
            ],200);


        }catch (Exception $e) {
            Log::error('Error updating user', ['error' => $e->getMessage()]);

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
            $user = User::where('id',$id)->first();

            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        }catch (ModelNotFoundException $e) {
            Log::error('User not found: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting user.',
            ], 500);
        }
    }
}
