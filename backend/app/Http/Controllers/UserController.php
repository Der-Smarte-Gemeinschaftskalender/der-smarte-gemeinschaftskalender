<?php

namespace App\Http\Controllers;

use App\Models\Mobilizon;
use App\Models\User;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Log;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('is_admin', only: ['instance']),
        ];
    }

    public function registerPerson(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $preferredUsername = $request->input('preferredUsername');

        $mclient = Mobilizon::getInstance();
        $response = $mclient->registerPerson([
            'name' => $name,
            'preferred_username' => $preferredUsername,
        ]);

        if ($response['data']['createPerson']) {
            $user = auth()->user();
            $user->mobilizon_name = $name;
            $user->mobilizon_preferred_username = $preferredUsername;
            $user->save();
            return response()->json([
                'message' => 'Person registered successfully',
                'data' => $response['data']['createPerson'],
            ]);
        }

        return $response['errors'][0]['code'] === 'validation'
            ? response()->json(['error' => $response['errors'][0]['message'][0]], 500)
            : response()->json(['error' => $response['errors'][0]['message']], 500);
    }


    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255'
            ],
            'mobilizon_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ]
        ]);


        if (!$validated) {
            return response()->json(['error' => 'Bitte alle Felder ausfüllen'], 400);
        }

        $user = auth()->user();

        try {
            if ($validated['email'] !== $user->email) {
                $existingUser = User::where('email', $validated['email'])->first();
                if ($existingUser) {
                    return response()->json(['error' => 'Diese E-Mail-Adresse ist bereits vergeben'], 400);
                }
                $user->email = $validated['email'];
            }

            if ($validated['mobilizon_name'] !== $user->mobilizon_name) {
                $mclient = Mobilizon::getInstance();
                $mresponse = $mclient->updatePerson($validated['mobilizon_name']);

                if ($mclient->hasError($mresponse)) {
                    return response()->json([
                        'error' => $mclient->getError($mresponse)
                    ], 400);
                } else {
                    $user->mobilizon_name = $validated['mobilizon_name'];
                }
            }

            $user->save();
            $mclient = Mobilizon::getInstance();

            return response()->json([
                'message' => 'Profil erfolgreich aktualisiert',
                'user' => [
                    'email' => $user->email
                ],
                'person' => $mclient->person
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Aktualisieren des Profils: ' . $e->getMessage());
            return response()->json(['error' => 'Fehler beim Aktualisieren des Profils: ' . $e->getMessage()], 500);
        }
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'new_password_confirm' => 'required|string|min:6',
        ]);

        if (!$validated) {
            return response()->json(['error' => 'Bitte alle Felder ausfüllen'], 400);
        }

        if ($validated['new_password'] !== $validated['new_password_confirm']) {
            return response()->json(['error' => 'Die neuen Passwörter stimmen nicht überein'], 400);
        }

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['error' => 'Aktuelles Passwort ist falsch'], 400);
        }

        $user->password = $validated['new_password'];
        $user->save();

        return response()->json([
            'message' => 'Passwort erfolgreich aktualisiert'
        ]);
    }

    public function instance(): JsonResponse
    {

        $user = auth()->user();

        return response()->json([
            'email' => $user->mobilizon_email,
            'password' => $user->mobilizon_password,
        ]);
    }
}
