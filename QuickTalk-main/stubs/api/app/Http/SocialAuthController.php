<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class SocialAuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle social login requests.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function socialLogin(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'provider' => 'required|in:google,facebook,apple',
        ]);

        $provider = $request->provider;
        $socialUser = null;

        try {
            switch ($provider) {
                case 'google':
                    $socialUser = $this->verifyGoogleToken($request->token);
                    break;
                case 'facebook':
                    $socialUser = $this->verifyFacebookToken($request->token);
                    break;
                case 'apple':
                    $socialUser = $this->verifyAppleToken($request->token);
                    break;
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Invalid or expired social token.',
                'error' => $e->getMessage()
            ], 401);
        }

        // Check if user exists by provider_id
        $user = User::where('email', $socialUser['email'] ?? null)
            ->orWhere(function ($query) use ($provider, $socialUser) {
                $query->where('provider', $provider)
                    ->where('provider_id', $socialUser['id']);
            })
            ->first();

        // Download avatar if provided
        $avatarPath = null;
        if (!empty($socialUser['avatar'])) {
            try {
                $avatarContents = Http::timeout(5)->get($socialUser['avatar'])->body();
                $imageName = Str::slug(time() . $socialUser['id']) . '.jpg';
                $folder = 'avatars';
                $path = public_path('uploads/' . $folder);
                if (!file_exists($path)) mkdir($path, 0755, true);
                file_put_contents($path . '/' . $imageName, $avatarContents);
                $avatarPath = 'uploads/' . $folder . '/' . $imageName;
            } catch (Exception $e) {
                $avatarPath = null; // fallback if avatar fails
            }
        }

        if (!$user) {
            // Create user if not exists
            $user = User::create([
                'name'     => $socialUser['name'] ?? 'User',
                'email'    => $socialUser['email'] ?? null,
                'avatar'   => $avatarPath,
                'provider' => $provider,
                'provider_id' => $socialUser['id'],
                'password' => bcrypt(Str::random(16)),
                'agree_to_terms' => false,
            ]);
        } else {
            // Update user info
            $user->update([
                'name' => $socialUser['name'] ?? $user->name,
                'avatar' => $avatarPath ?? $user->avatar,
            ]);
        }

        // Create Sanctum token instead of JWT
        $token = $user->createToken('auth_token')->plainTextToken;
        $user->setAttribute('token', $token);

        return $this->success($user, 'Social login successful.', 200);
    }

    // -------------------------
    // GOOGLE
    // -------------------------
    private function verifyGoogleToken($token)
    {
        $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'access_token' => $token
        ]);

        if (!$response->ok()) throw new Exception('Invalid Google token');

        $data = $response->json();

        return [
            'id' => $data['sub'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? explode('@', $data['email'])[0] ?? 'User',
            'avatar' => $data['picture'] ?? null,
        ];
    }


    private function verifyFacebookToken($token)
    {
        $response = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture',
            'access_token' => $token
        ]);

        if (!$response->ok()) throw new Exception('Invalid Facebook token');

        $data = $response->json();
        return [
            'id' => $data['id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'avatar' => $data['picture']['data']['url'] ?? null,
        ];
    }

    private function verifyAppleToken($token)
    {
        // Apple token verification is done using JWT verification (You can use Firebase JWT package)
        // Here is a simplified placeholder
        // You must decode and verify the token properly in production
        $payload = json_decode(base64_decode(explode('.', $token)[1]), true);

        return [
            'id' => $payload['sub'],
            'email' => $payload['email'] ?? null,
            'name' => null, // Apple only sends name on first login
            'avatar' => null,
        ];
    }
}
