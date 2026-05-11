<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
                $user = User::all();
                return ApiResponse::success('User retrieved successfully',
                $user,
                200,
            );
        }catch (Exception $e)
        {
            return ApiResponse::error('Failed to retrieve users',
            $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
                 // Validate
                $validator = Validator::make($request->all(), [
                    'role_id' => ['required','integer','exists:roles,id'],
                    'name' => ['required','string','max:255'],
                    'email' => ['required','email','max:255','unique:users,email'],
                    'phone' => ['nullable','string','max:20'],
                    'status' => ['nullable','boolean'],
                    'password' => ['required','string','min:6'],
                ]);

                if ($validator->fails()) {
                    return ApiResponse::error('Validation failed', $validator->errors(), 422);
                }

                // Create
                User::create([
                    'role_id' => $request->role_id,
                    'name' => $request->string('name'),
                    'email' => $request->string('email'),
                    'phone' => $request->phone ?? null,
                    'status' => $request->status ?? true,
                    'password' => Hash::make($request->string('password')),
                    'email_verified_at' => now(),
                ]);

                return ApiResponse::success('Created user successfully', [], 201);
        }
        catch (Exception $e) {
            return ApiResponse::error('Failed to create user',
            $e->getMessage());
        }

    }

    public function show(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::error('Không tìm thấy user', null, 404);
        }
        return ApiResponse::success('Lấy user thành công', $user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
             $user = User::find($id);

            if (!$user) {
                return ApiResponse::error('Không tìm thấy user', null, 404);
            }

            // Validate (update thì cho phép "sometimes")
            $validator = Validator::make($request->all(), [
                'role_id' => ['sometimes','integer','exists:roles,id'],
                'name' => ['sometimes','string','max:255'],
                'email' => ['sometimes','email','max:255','unique:users,email,' . $user->id],
                'phone' => ['nullable','string','max:20'],
                'status' => ['sometimes','boolean'],
                'password' => ['sometimes','string','min:6'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Update fields nếu có gửi lên
            if ($request->filled('role_id')) $user->role_id = $request->role_id;
            if ($request->filled('name')) $user->name = $request->string('name');
            if ($request->filled('email')) $user->email = $request->string('email');
            if ($request->has('phone')) $user->phone = $request->phone;
            if ($request->filled('status')) $user->status = $request->boolean('status');
            if ($request->filled('password')) $user->password = Hash::make($request->string('password'));

            $user->save();

            return ApiResponse::success('Cập nhật user thành công', [
            ], 200);
        }
        catch (Exception $e) {
            return ApiResponse::error('Cập nhật user thất bại',
            $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::error('Không tìm thấy user', null, 404);
        }
        $user->delete();
        return ApiResponse::success('Xóa user thành công', null, 200);
    }
}
