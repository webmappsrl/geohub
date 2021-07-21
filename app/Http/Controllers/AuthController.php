<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Signup and get a JWT
     *
     * @return JsonResponse
     */
    public function signup(): JsonResponse
    {
        $credentials = request(['email', 'password']);

        if (!isset($credentials['email']) || !isset($credentials['password'])) {
            $message = "";
            if (!isset($credentials['email'])) $message .= "email";
            if (!isset($credentials['password']))
                $message .= (!empty($message) ? ', ' : '') . "email";

            return response()->json([
                'error' => "Missing mandatory parameter(s): " . $message,
                'code' => 400
            ], 400);
        }

        $token = auth('api')->attempt($credentials);
        if ($token) {
            $tokenArray = $this->respondWithToken($token);

            return response()->json(array_merge($this->me()->getData('true'), $tokenArray->getData('true')));
        }

        $credentials = array_merge($credentials, request(['name']));

        if (!isset($credentials['name'])) {
            return response()->json([
                'error' => "Missing mandatory parameter(s): 'name'",
                'code' => 400
            ], 400);
        }

        $user = new User();
        $user->password = bcrypt($credentials['password']);
        $user->name = $credentials['name'];
        $user->email = $credentials['email'];
        $user->email_verified_at = now();
        $user->save();
        $role = Role::where('name', '=', 'Contributor')->first();
        $user->roles()->sync([$role->id]);

        $token = auth('api')->attempt($credentials);
        if ($token) {
            $tokenArray = $this->respondWithToken($token);

            return response()->json(array_merge($this->me()->getData('true'), $tokenArray->getData('true')));
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return JsonResponse
     */
    public function login(): JsonResponse
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $tokenArray = $this->respondWithToken($token);

        return response()->json(array_merge($this->me()->getData('true'), $tokenArray->getData('true')));
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $roles = array_map(function ($value) {
            return strtolower($value);
        }, $user->roles->pluck('name')->toArray());

        return response()->json(array_merge($user->toArray(), ['roles' => $roles]));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
