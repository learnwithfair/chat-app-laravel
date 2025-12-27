<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    use ApiResponse;
    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        // 1. Validate the incoming request
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed', 422);
        }
        $user = User::where('email', $request->email)->first();
        if (! Hash::check($request->password, $user->password)) {
            return $this->error(null, 'Invalid email or password', 401);
        }

        $user = User::where('email', $request->email)->first();

        // Create token
        $token = $user->createToken('postman')->plainTextToken;

        return $this->success([
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Handle a logout request to the application.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
