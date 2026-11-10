<?php

namespace App\Http\Controllers;


use App\Models\Mobilizon;
use App\Models\OrganisationStatus;
use App\Models\User;
use DB;
use App\Http\Controllers\OrganisationController;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Mail\SendConfirmEmail;
use Mail;
use Log;
use Exception;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     */
    public function __construct() {}

    /**
     * Get a JWT via given credentials.
     *
     */
    public function login(): JsonResponse
    {
        $credentials = request(['email', 'password']);
        $token = auth()->attempt([
            'email' => strtolower($credentials['email']),
            'password' => $credentials['password'],
        ]);

        if (!$token) {
            return response()->json(['error' => __('messages.auth.unauthorized')], 401);
        }

        $mclient = Mobilizon::getInstance();
        if (!$mclient->user) {
            return response()->json(['error' => 'user_not_validated'], 404);
        }

        $user = auth()->user();
        if (!$user->is_active) {
            return response()->json(['error' => 'user_deactivated'], 403);
        }

        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'person' => $mclient->person
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validInputs = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);
        $email = strtolower($request->email);
        // Don't differentiate between email already exists for security reasons
        if (!$validInputs || User::where('email', $email)->exists()) {
            return response()->json(['error' => __('messages.auth.userValidationFailed')], 422);
        }

        $mclient = Mobilizon::getInstance(true);
        $mobilizon_password = Str::random(32);
        $createdMobilizonUser = $mclient->createUser($email, $mobilizon_password);

        if (isset($createdMobilizonUser['errors'])) {
            return response()->json(['error' => $createdMobilizonUser['errors'][0]['message']], 500);
        }

        if (!isset($createdMobilizonUser['data']['createUser']['id'])) {
            Log::error('Mobilizon createUser lieferte keine gültige Antwort', ['response' => $createdMobilizonUser]);

            return response()->json([
                'error' => __('messages.auth.registrationUnavailable')
            ], 503);
        }

        $user = new User();
        $user->email = $email;
        // Emails are sent in the language the user registered with (resolved from Accept-Language by SetLocale)
        $user->locale = App::getLocale();
        $user->password = Hash::make($request->input('password'));
        $user->mobilizon_email = $email;
        $user->mobilizon_password = $mobilizon_password;
        $user->email_verification_token = Str::uuid()->toString();
        $user->mobilizon_user_id = $createdMobilizonUser['data']['createUser']['id'];
        $user->save();

        try {
            Mail::to($user)
                ->send(new SendConfirmEmail($user->email_verification_token));
        } catch (\Exception $e) {
            Log::error('Error sending confirmation email: ' . $e->getMessage());
        }

        $token = auth()->login($user);

        return response()->json([
            'access_token' => $token,
            'user' => $user
        ]);
    }

    public function registerWithProfileAndOrganisation(Request $request): JsonResponse
    {
        if (!config('dsg.strict_mode')) {
            return response()->json(['error' => __('messages.auth.strictModeOnly')], 403);
        }

        $validatedUserData = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $validatedProfileData = $request->validate([
            'name' => 'required|string|max:255',
            'preferredUsername' => 'required|string|max:255',
        ]);

        $validatedOrganisationData = $request->validate([
            'organisation_name' => 'required|string|max:255',
            'organisation_preferredUsername' => 'required|string|max:255',
            'organisation_summary' => 'nullable|string',
        ]);

        $email = strtolower($request->input('email'));

        // Don't differentiate between email already exists for security reasons
        if (!$validatedUserData || User::where('email', $email)->exists()) {
            return response()->json(['error' => __('messages.auth.userValidationFailed')], 422);
        }

        if (!$validatedProfileData) {
            return response()->json(['error' => __('messages.auth.profileValidationFailed')], 422);
        }

        if (!$validatedOrganisationData) {
            return response()->json(['error' => __('messages.auth.organisationValidationFailed')], 422);
        }

        if ($validatedOrganisationData['organisation_preferredUsername'] === $validatedProfileData['preferredUsername']) {
            return response()->json(['error' => __('messages.auth.organisationUsernameEqualsProfile')], 422);
        }

        $adminMclient = Mobilizon::getInstanceAdmin();

        $existingGroups = $adminMclient->adminGetAllGroupsArray();
        foreach ($existingGroups as $group) {
            if ($group['preferredUsername'] === $validatedOrganisationData['organisation_preferredUsername']) {
                return response()->json(['error' => __('messages.auth.organisationUsernameTaken')], 422);
            }
        }

        $userWithSameUsernameExists = (bool) $adminMclient->findProfileByPreferredUsername($validatedOrganisationData['organisation_preferredUsername']);
        if ($userWithSameUsernameExists) {
            return response()->json(['error' => __('messages.organisations.usernameUsedByUser')], 422);
        }

        // Step 1: Create User (Mobilizon + local)
        $mobilizon_password = Str::random(32);
        $createdMobilizonUser = $adminMclient->createUser($email, $mobilizon_password);

        if (isset($createdMobilizonUser['errors'])) {
            return response()->json(['error' => $createdMobilizonUser['errors'][0]['message'][0]], 500);
        }

        $user = new User();
        $user->email = $email;
        // Emails are sent in the language the user registered with (resolved from Accept-Language by SetLocale)
        $user->locale = App::getLocale();
        $user->password = $request->input('password');
        $user->mobilizon_email = $email;
        $user->mobilizon_password = $mobilizon_password;
        $user->email_verification_token = Str::uuid()->toString();
        $user->mobilizon_user_id = $createdMobilizonUser['data']['createUser']['id'];
        $user->admin_note = 'Automatisch erstellter Nutzer mit Profil und Organisationsanfrage';
        $user->save();

        // Step 2.1: Activate the user in Mobilizon
        $activateResponse = $adminMclient->adminUpdateUser([
            'id' => $user->mobilizon_user_id,
            'confirmed' => true
        ]);

        if ($adminMclient->hasError($activateResponse)) {
            $user->delete();
            $adminMclient->deleteAccount($user->mobilizon_user_id, $user->mobilizon_password);
            return response()->json(['error' => __('messages.auth.userValidationFailed')], 500);
        } else {
            $user->is_active = true;
            $user->save();
        }

        // Step 2.2: Get new Mobilizon Client instance for the user
        $mclient = Mobilizon::getInstance(false, $user, true);

        if (!$mclient->user) {
            Log::error('User mobilizon login failed for user ID ' . $user->id);
            $user->delete();
            $mclient->deletePerson($user->mobilizon_profile_id);
            $mclient->deleteAccount($user->mobilizon_user_id, $user->mobilizon_password);
            return response()->json(['error' => __('messages.auth.mobilizonLoginFailed')], 500);
        }

        // Step 2.3: Create Profile
        $profileResponse = $mclient->registerPerson([
            'name' => $validatedProfileData['name'],
            'preferred_username' => $validatedProfileData['preferredUsername']
        ]);

        if (isset($profileResponse['errors'])) {
            $user->delete();
            $mclient->deleteAccount($user->mobilizon_user_id, $user->mobilizon_password);
            return response()->json(['error' => __('messages.auth.profileCreationFailed', ['message' => $profileResponse['errors'][0]['message'][0]])], 500);
        } else {
            $user->mobilizon_name = $profileResponse['data']['createPerson']['name'];
            $user->mobilizon_preferred_username = $profileResponse['data']['createPerson']['preferredUsername'];
            $user->mobilizon_profile_id = $profileResponse['data']['createPerson']['id'];
            $user->save();
        }

        // Step 2.4: Reload Mobilizon client to load person
        $mclient = Mobilizon::getInstance(false, $user, true);

        if (!$mclient->person) {
            $user->delete();
            $mclient->deletePerson($user->mobilizon_profile_id);
            $mclient->deleteAccount($user->mobilizon_user_id, $user->mobilizon_password);
            Log::error('Mobilizon person not loaded after creation', [
                'user_id' => $user->id,
                'mobilizon_user_id' => $user->mobilizon_user_id,
            ]);

            return response()->json([
                'error' => __('messages.auth.personNotAvailable')
            ], 500);
        }

        // Step 4: Send Emails
        try {
            Mail::to($user)
                ->send(new SendConfirmEmail($user->email_verification_token));
        } catch (Exception $e) {
            Log::error('Error sending confirmation email: ' . $e->getMessage());
        }

        // Step 5: Create Organisation Status + Approval Request (should be done by admin later)
        $organisationCreateRequest = new Request([
            'name' => $validatedOrganisationData['organisation_name'],
            'preferredUsername' => $validatedOrganisationData['organisation_preferredUsername'],
            'summary' => $validatedOrganisationData['organisation_summary'] ?? '',
        ]);

        // Important: set the user!
        $organisationCreateRequest->setUserResolver(function () use ($user) {
            return $user;
        });

        $organisationController = new OrganisationController();
        $organisationResponse = $organisationController->requestOrganisationCreation($organisationCreateRequest);

        $token = auth()->login($user);

        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'person' => $mclient->person
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validInputs = $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($request->input('email'));

        if (!$validInputs) {
            return response()->json(['error' => __('messages.auth.invalidEmail')], 422);
        }

        if (!User::where('email', $email)->exists()) {
            return response()->json(['message' => __('messages.auth.resetLinkSent')]);
        }

        $status = Password::sendResetLink(
            ['email' => $email]
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __('messages.auth.resetLinkSent')])
            : response()->json(['error' => __('messages.auth.resetLinkFailed')], 500);
    }

    public function validateUser(Request $request): JsonResponse
    {
        $verificationToken = $request->input('verificationToken');

        $user = User::where('email_verification_token', $verificationToken)->first();
        if (!$user) {
            return response()->json(['error' => __('messages.auth.userValidationFailed')], 500);
        }
        $systemAdmin = User::find(1);

        $mclient = Mobilizon::getInstance(false, $systemAdmin);

        if (!$user->is_active) {
            $mresponse = $mclient->adminUpdateUser([
                'id' => $user->mobilizon_user_id,
                'confirmed' => true
            ]);

            if ($mclient->hasError($mresponse)) {
                Log::error('Mobilizon user activation failed for user ID ' . $user->id, ['response' => $mresponse]);
                return response()->json(['error' => __('messages.auth.userValidationFailed')], 500);
            }
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->is_active = true;
        $user->save();

        return response()->json(['message' => __('messages.auth.userValidated')]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6|same:password'
        ]);

        if (!$validated) {
            return response()->json(['error' => __('messages.auth.invalidInput')], 422);
        }

        $plainToken = $validated['token'];
        $record = DB::table('password_reset_tokens')
            ->get()
            ->first(function ($row) use ($validated, $plainToken) {
                return isset($row->token) && Hash::check($plainToken, $row->token);
            });

        if (!$record) {
            return response()->json(['error' => __('messages.auth.invalidToken')], 400);
        }

        $status = Password::reset(
            [
                'email' => $record->email,
                ...$request->only('password', 'token')
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __('messages.auth.passwordReset')])
            : response()->json([
                'error' =>  __('messages.auth.passwordResetFailed'),
                'status' => $status,
                'rp_record_email' => $record->email,
                'rp_record_token' => $record->token
            ], 500);
    }

    /**
     * Get the authenticated User.
     *
     */
    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => __('messages.auth.loggedOut')]);
    }

    /**
     * Refresh a token.
     *
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
