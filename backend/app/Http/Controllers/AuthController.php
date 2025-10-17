<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Mail\SendConfirmEmail;
use App\Mail\ResetPasswordEmail;
use App\Models\Mobilizon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Mail;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
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

    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $valideInputs = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!$valideInputs) {
            return response()->json(['error' => 'Validation failed'], 422);
        }
        if (User::where('email', $request->input('email'))->exists()) {
            return response()->json(['error' => 'Email already exists'], 409);
        }

        $mclient = Mobilizon::getInstance(true);
        $mobilizon_password = Str::random(32);
        $createdMobilizonUser = $mclient->createUser($request->input('email'), $mobilizon_password);

        if (isset($createdMobilizonUser['errors'])) {
            return response()->json(['error' => $createdMobilizonUser['errors'][0]['message']], 500);
        }

        $user = new User();
        $user->email = $request->input('email');
        $user->password = $request->input('password');
        $user->mobilizon_email = $request->input('email');
        $user->mobilizon_password = $mobilizon_password;
        $user->email_verification_token = Str::uuid()->toString();
        $user->mobilizon_user_id = $createdMobilizonUser['data']['createUser']['id'];
        $user->save();

        try {
            Mail::to($user->email)
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

    public function forgotPassword(Request $request)
    {
        $validInputs = $request->validate([
            'email' => 'required|email',
        ]);

        if (!$validInputs) {
            return response()->json(['error' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein'], 422);
        }

        if (!User::where('email', $request->input('email'))->exists()) {
            return response()->json(['message' => 'Wenn diese E-Mail-Adresse in unserem System vorhanden ist, erhalten Sie eine E-Mail mit einem Link zum Zurücksetzen Ihres Passworts.']);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Wenn diese E-Mail-Adresse in unserem System vorhanden ist, erhalten Sie eine E-Mail mit einem Link zum Zurücksetzen Ihres Passworts.'])
            : response()->json(['error' => 'Fehler beim Senden des Passwort-Zurücksetzen-Links'], 500);
    }

    public function validateUser(Request $request)
    {
        $verificationToken = $request->input('verificationToken');

        $user = User::where('email_verification_token', $verificationToken)->first();
        if (!$user) {
            return response()->json(['error' => 'User validation failed'], 500);
        }
        $systemAdmin = User::find(1);

        $mclient = Mobilizon::getInstance(false, $systemAdmin);

        $mresponse = $mclient->adminUpdateUser([
            'id' => $user->mobilizon_user_id,
            'confirmed' => true
        ]);

        if ($mclient->hasError($mresponse)) {
            return response()->json(['error' => 'User validation failed'], 500);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->is_active = true;
        $user->save();

        return response()->json(['message' => 'User validated successfully']);
    }

    public function resetPassword(Request $request)
    {
        $validInputs = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!$validInputs) {
            return response()->json(['error' => 'Fehlende oder ungültige Eingaben'], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Passwort erfolgreich zurückgesetzt'])
            : response()->json(['error' =>  'Fehler beim Zurücksetzen des Passworts'], 500);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
