<?php

namespace App\Http\Controllers;

use App\Mail\SendNotifcationConfirm;
use App\Models\Notification;
use App\Models\NotificationDisallow;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Mail;
use Str;

class NotificationController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        try {
            $existingNotification = Notification::where('email', $request->input('email'))
                ->where('is_verified', true)
                ->first();

            $disallowedNotification = NotificationDisallow::where('email', $request->input('email'))->first();

            if ($existingNotification || $disallowedNotification) {
                return response()->json([
                    'error' => 'Fehler beim Speichern der Benachrichtigung.',
                    'message' => ''
                ], 500);
            }

            $notification = new Notification();

            $notification->intervall = $request->input('intervall');
            $notification->category = $request->input('category');
            $notification->email = $request->input('email');


            if ($request->input('eventType') === 'INTERNAL') {
                $notification->organisation_id = $request->input('organisation');
            } else if ($request->input('eventType') === 'GLOBAL') {
                $notification->organisation_id = $request->input('organisation');
                $notification->location_hash = $request->input('location_hash');
                $notification->address = $request->input('address');
                $notification->radius = $request->input('radius');
            }

            $notification->token = (string) Str::uuid7();
            $notification->save();

            try {
                Mail::to($notification->email)
                    ->send(new SendNotifcationConfirm(
                        $notification->email,
                        $notification->token
                    ));
            } catch (Exception $e) {
                Log::error('Failed to create notification', [
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'error' => 'Fehler beim Erstellen der Benachrichtigung.',
                    'message' => $e->getMessage()
                ], 500);
            }

            return response()->json([
                'message' => 'Erfolgreich abonniert.',
                'notification' => $notification
            ], 201);
        } catch (Exception $e) {
            Log::error('Fehler beim Speichern der Benachrichtigung.', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Fehler beim Speichern der Benachrichtigung.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        if (!$request->input('token')) {
            return response()->json([
                'error' => 'Token is required'
            ], 400);
        }

        try {
            $notification = Notification::where('token', $request->input('token'))
                ->where('is_verified', true)
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Diese E-Mail hat kein Abonnement.'
                ], 404);
            }

            $notification->intervall = $request->input('intervall', $notification->intervall);
            $notification->category = $request->input('category', $notification->category);
            $notification->email = $request->input('email', $notification->email);

            if ($request->input('eventType') === 'INTERNAL') {
                $notification->organisation_id = $request->input('organisation', $notification->organisation_id);
                $notification->location_hash = null;
                $notification->address = null;
                $notification->radius = null;
            } else if ($request->input('eventType') === 'GLOBAL') {
                $notification->organisation_id = null;
                $notification->location_hash = $request->input('location_hash', $notification->location_hash);
                $notification->address = $request->input('address', $notification->address);
                $notification->radius = $request->input('radius', $notification->radius);
            }

            $notification->save();

            return response()->json([
                'message' => 'Benachrichtigung erfolgreich aktualisiert.',
                'notification' => $notification
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Fehler beim Aktualisieren der Benachrichtigung.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function confirmEmailNotification(Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('email', $request->input('email'))
                ->where('token', $request->input('verificationToken'))
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Verifizierungstoken nicht gefunden.'
                ], 404);
            }

            $notification->is_verified = true;
            $notification->save();

            return response()->json([
                'message' => 'Benachrichtigung erfolgreich bestätigt.',
                'notification' => $notification
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Fehler bei der Bestätigung der Benachrichtigung.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addEmailDisallowNotification(Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('email', $request->input('email'))
                ->where('verification_token', $request->input('verificationToken'))
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Verifizierungstoken nicht gefunden.'
                ], 404);
            }

            $notification->delete();

            $disallowedNotification = new NotificationDisallow();
            $disallowedNotification->email = $request->input('email');
            $disallowedNotification->save();

            return response()->json([
                'message' => 'Benachrichtigung erfolgreich abgelehnt.',
                'notification' => $notification
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Fehler beim Ablehnen der Benachrichtigung.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function unsubscribeEmailNotification(Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('token', $request->input('token'))
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Diese E-Mail hat kein Abonnement.'
                ], 404);
            }

            if ($request->input('disallow')) {
                $disallowedNotification = new NotificationDisallow();
                $disallowedNotification->email = $notification->email;
                $disallowedNotification->save();
            }

            $notification->delete();

            return response()->json([
                'message' => 'Erfolgreich abgemeldet.',
                'notification' => $notification
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Fehler beim Abmelden.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('token', $request->input('token'))
                ->where('is_verified', true)
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Diese E-Mail hat kein Abonnement.'
                ], 404);
            }

            return response()->json([
                'message' => 'Abonnement gefunden.',
                'notification' => $notification
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Fehler beim Abrufen des Abonnements.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
