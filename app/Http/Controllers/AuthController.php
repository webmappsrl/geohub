<?php

namespace App\Http\Controllers;

use App\Models\Partnership;
use App\Models\User;
use App\Models\EcMedia;
use App\Models\TaxonomyActivity;
use App\Providers\PartnershipValidationProvider;
use Exception;
use Illuminate\Http\JsonResponse;
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
        $credentials = request(['email', 'password', 'name', 'last_name', 'referrer', 'fiscal_code']);

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

        // Check if user already exists
        $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password']
        ]);
        if ($token) {
            // TODO: save name/last_name/fiscal_code if empty
            $tokenArray = $this->respondWithToken($token);

            return response()->json(array_merge($this->me()->getData('true'), $tokenArray->getData('true')));
        }

        if (!isset($credentials['name'])) {
            return response()->json([
                'error' => "Missing mandatory parameter(s): 'name'",
                'code' => 400
            ], 400);
        }

        $user = new User();
        $user->password = bcrypt($credentials['password']);
        $user->name = $credentials['name'];
        if (isset($credentials['last_name']))
            $user->last_name = $credentials['last_name'];
        $user->email = $credentials['email'];
        $user->email_verified_at = now();
        if (isset($credentials['referrer']))
            $user->referrer = $credentials['referrer'];
        if (isset($credentials['fiscal_code']) && is_string($credentials['fiscal_code']) && strlen($credentials['fiscal_code']) === 16)
            $user->fiscal_code = strtoupper($credentials['fiscal_code']);

        $user->save();
        $role = Role::where('name', '=', 'Contributor')->first();
        $user->roles()->sync([$role->id]);

        $partnerships = Partnership::all();
        $service = app(PartnershipValidationProvider::class);
        $partnershipsIds = [];
        foreach ($partnerships as $partnership) {
            if (
                method_exists(PartnershipValidationProvider::class, $partnership->validator)
                && $service->{$partnership->validator}($user)
            ) {
                $partnershipsIds[] = $partnership->id;
            }
        }
        $user->partnerships()->sync($partnershipsIds);

        $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password']
        ]);
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
     * Delete user.
     *
     * @return JsonResponse
     */
    public function delete(): JsonResponse
    {
        try {
            $userFromAPI = auth('api')->user();
            $user = User::find($userFromAPI->id);
            if (!$user->hasRole('Contributor')) {
                throw new Exception("this user can't be deleted by api");
            }
            auth('api')->logout();
            $user->delete();
        } catch (Exception $e) {
            return response()->json([
                'error' => "this user can't be deleted by api",
                'code' => 400
            ], 400);
        }
        return response()->json(['success' => 'account user deleted']);
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

        $partnerships = array_map(function ($value) {
            return $value;
        }, $user->partnerships->pluck('name')->toArray());

        $result = array_merge($user->toArray(), [
            'roles' => $roles,
            'partnerships' => $partnerships
        ]);

        unset($result['referrer']);
        unset($result['password']);

        return response()->json($result);
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
