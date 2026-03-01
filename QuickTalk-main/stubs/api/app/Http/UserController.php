<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Fetch Login User Data
     *
     * @return \Illuminate\Http\JsonResponse  JSON response with success or error.
     */

    public function userData()
    {

        $user = auth()->user();

        if (!$user) {
            return $this->error([], 'User Not Found', 404);
        }

        return $this->success($user, 'User data fetched successfully', 200);
    }

    /**
     * Update User Information
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request with the register query.
     * @return \Illuminate\Http\JsonResponse  JSON response with success or error.
     */

    public function userUpdate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'avatar'  => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            'name'    => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        try {
            // Find the user by ID
            $user = auth()->user();

            // If user is not found, return an error response
            if (!$user) {
                return $this->error([], "User Not Found", 404);
            }

            if ($request->hasFile('avatar')) {

                if ($user->avatar) {
                    $previousImagePath = public_path($user->avatar);
                    if (file_exists($previousImagePath)) {
                        unlink($previousImagePath);
                    }
                }

                $image     = $request->file('avatar');
                $imageName = 'storage/' . $request->file('avatar')->store('User/Avatar', 'public');
            } else {
                $imageName = $user->avatar;
            }

            $user->name    = $request->name;
            $user->avatar  = $imageName;

            $user->save();

            return $this->success($user, 'User updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Logout the authenticated user's account
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse JSON response with success or error.
     */
    public function logoutUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $user = $request->user();
            if ($request->has('device_id')) {
                $user->firebaseTokens()->where('device_id', $request->device_id)->delete();
            }

            // Revoke the token of the currently authenticated user
            $request->user()->currentAccessToken()->delete();

            return $this->success([], 'Successfully logged out', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Delete the authenticated user's account
     *
     * @return \Illuminate\Http\JsonResponse JSON response with success or error.
     */
    public function deleteUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            // Get the authenticated user
            $user = auth()->user();

            // If user is not found, return an error response
            if (!$user) {
                return $this->error([], "User Not Found", 404);
            }

            if ($request->has('device_id')) {
                $user->firebaseTokens()->where('device_id', $request->device_id)->delete();
            }

            // Delete the user's avatar if it exists
            if ($user->avatar) {
                $previousImagePath = public_path($user->avatar);
                if (file_exists($previousImagePath)) {
                    unlink($previousImagePath);
                }
            }

            // Delete the user
            $user->delete();

            return $this->success([], 'User deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Delete Firebase Token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFirebaseToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $user = $request->user();
            $user->firebaseTokens()->where('device_id', $request->device_id)->delete();

            return $this->success([], 'Firebase token deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
