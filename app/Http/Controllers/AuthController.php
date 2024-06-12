<?php

namespace App\Http\Controllers;

use App\Models\Partnership;
use App\Models\User;
use App\Providers\PartnershipValidationProvider;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /** * Signup and get a JWT 
     *
     *  @param Request $request 
     *  @return JsonResponse 
     */
    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'name' => 'required|string|max:255',
            ],
            [
                'email.email' => 'Il campo email deve essere un indirizzo email valido.',
                'email.unique' => 'Un utente è già stato registrato con questa email.',
                'email.required' => 'Il campo email è obbligatorio.',
                'password.required' => 'Il campo password è obbligatorio.',
                'name.required' => 'Il campo nome è obbligatorio.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'code' => 400
            ], 400);
        }

        $credentials = $request->only(['email', 'password', 'name']);

        // Check if user already exists
        if ($token = auth('api')->attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return $this->loginResponse($token);
        }

        try {
            $user = $this->createUser($credentials);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => 400
            ], 400);
        }

        if ($token = auth('api')->attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return $this->loginResponse($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ],
            [
                'email.required' => 'Il campo email é obbligatorio.',
                'email.email' => 'Il campo email deve essere un indirizzo email valido.',
                'password.required' => 'Il campo password è obbligatorio.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'code' => 401
            ], 401);
        }

        $credentials = $request->only(['email', 'password']);

        //check if email exists
        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return response()->json([
                'error' => 'L\'email inserita non è corretta. Per favore, riprova.',
                'code' => 401
            ], 401);
        }

        // Check if password is correct
        if (!auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'La password inserita non è corretta. Per favore, riprova.',
                'code' => 401
            ], 401);
        }

        $token = auth('api')->attempt($credentials);

        return $this->loginResponse($token);
    }


    /**
     * Delete the authenticated user.
     *
     * This function deletes the user that is currently authenticated via the API.
     * The user can only be deleted if they have the 'Contributor' role.
     *
     * @return JsonResponse The JSON response containing a success message if the user was deleted successfully,
     *                      or an error message and code if the deletion failed.
     * @throws Exception If the user does not have the 'Contributor' role.
     */
    public function delete(): JsonResponse
    {
        try {
            // Get the authenticated user from the API
            $userFromAPI = auth('api')->user();
            $user = User::find($userFromAPI->id);

            // Check if the user has the 'Contributor' role
            if (!$user->hasRole('Contributor')) {
                throw new Exception("Questo utente non può essere cancellato tramite API.");
            }

            // Logout the user from the API and delete the user
            auth('api')->logout();
            $user->delete();
        } catch (Exception $e) {
            // If an exception occurs, return a JSON response with the error message and code
            return response()->json([
                'error' => $e->getMessage(),
                'code' => 400
            ], 400);
        }

        // If the user was deleted successfully, return a JSON response with a success message
        return response()->json(['success' => 'Account utente cancellato con successo.']);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $roles = array_map('strtolower', $user->roles->pluck('name')->toArray());
        $partnerships = $user->partnerships->pluck('name')->toArray();

        $result = array_merge($user->toArray(), [
            'roles' => $roles,
            'partnerships' => $partnerships
        ]);

        unset($result['referrer'], $result['password']);

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

        return response()->json(['message' => 'Logout effettuato con successo.']);
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

    /**
     * Generate the login response with user data and token.
     *
     * @param string $token
     * @return JsonResponse
     */
    protected function loginResponse(string $token): JsonResponse
    {
        $tokenArray = $this->respondWithToken($token);
        return response()->json(array_merge($this->me()->getData(true), $tokenArray->getData(true)));
    }

    /**
     * Create a new user and handle partnerships
     *
     * @param array $data
     * @return User
     * @throws Exception
     */
    private function createUser(array $data): User
    {
        $user = new User();
        $user->fill([
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'name' => $data['name'],
            'email_verified_at' => now(),
        ]);

        try {
            $user->save();
        } catch (Exception $e) {
            throw new Exception('Errore durante il salvataggio dell\'utente. Per favore, riprova.');
        }

        $this->assignRole($user);

        $this->assignPartnerships($user);

        return $user;
    }

    /**
     * Assigns the 'Contributor' role to the user.
     *
     * @param User $user The user to assign the role to.
     *
     * @throws Exception If the 'Contributor' role is not found.
     *
     * @return void
     */
    private function assignRole(User $user): void
    {
        // Find the 'Contributor' role
        $role = Role::where('name', 'Contributor')->first();

        // If the role is not found, throw an exception
        if (!$role) {
            throw new Exception('Ruolo Contributor non trovato. Per favore, contatta l\'amministratore.');
        }

        // Sync the user's roles, assigning the 'Contributor' role
        $user->roles()->sync([$role->id]);
    }


    /**
     * Assigns partnerships to the user based on their validation.
     *
     * @param User $user The user to assign partnerships to.
     *
     * @throws Exception If there is an error during the assignment process.
     *
     * @return void
     */
    private function assignPartnerships(User $user): void
    {
        // Retrieve all partnerships
        $partnerships = Partnership::all();

        // Instantiate the partnership validation service
        $service = app(PartnershipValidationProvider::class);

        // Initialize an array to store the partnerships IDs
        $partnershipsIds = [];

        // Iterate over each partnership
        foreach ($partnerships as $partnership) {
            // Check if the validator method exists and if the user passes the validation
            if (
                method_exists(PartnershipValidationProvider::class, $partnership->validator) &&
                $service->{$partnership->validator}($user)
            ) {
                // Add the partnership ID to the array
                $partnershipsIds[] = $partnership->id;
            }
        }

        try {
            // Sync the user's partnerships, assigning the ones that passed the validation
            $user->partnerships()->sync($partnershipsIds);
        } catch (Exception $e) {
            throw new Exception('Errore durante l\'assegnazione delle partnership. Per favore, riprova.');
        }
    }
}