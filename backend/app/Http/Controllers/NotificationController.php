<?php

namespace App\Http\Controllers;

use App\Mail\SendNotifcationConfirm;
use App\Models\Notification;
use App\Models\NotificationDisallow;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
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
                    'error' => __('messages.notifications.saveFailed'),
                    'message' => ''
                ], 500);
            }

            $notification = new Notification();

            $notification->intervall = $request->input('intervall');
            $notification->category = $request->input('category');
            $notification->email = $request->input('email');
            // The newsletter is sent in the language of the subscription request (see SetLocale)
            $notification->locale = App::getLocale();


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
                        $notification->token
                    ));
            } catch (Exception $e) {
                Log::error('Failed to create notification', [
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'error' => __('messages.notifications.createFailed'),
                    'message' => $e->getMessage()
                ], 500);
            }

            return response()->json([
                'message' => __('messages.notifications.subscribed'),
                'notification' => $notification
            ], 201);
        } catch (Exception $e) {
            Log::error('Fehler beim Speichern der Benachrichtigung.', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => __('messages.notifications.saveFailed'),
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        if (!$request->input('token')) {
            return response()->json([
                'error' => __('messages.notifications.tokenRequired')
            ], 400);
        }

        try {
            $notification = Notification::where('token', $request->input('token'))
                ->where('is_verified', true)
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => __('messages.notifications.noSubscription')
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
                'message' => __('messages.notifications.updated'),
                'notification' => $notification
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => __('messages.notifications.updateFailed'),
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function confirmEmailNotification(Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('token', $request->input('verificationToken'))
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => __('messages.notifications.tokenNotFound')
                ], 404);
            }

            $notification->is_verified = true;
            $notification->save();

            return response()->json([
                'message' => __('messages.notifications.confirmed'),
                'notification' => $notification
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => __('messages.notifications.confirmFailed'),
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addEmailDisallowNotification(Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('verification_token', $request->input('verificationToken'))
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => __('messages.notifications.tokenNotFound')
                ], 404);
            }

            $notification->delete();

            $disallowedNotification = new NotificationDisallow();
            $disallowedNotification->email = $request->input('email');
            $disallowedNotification->save();

            return response()->json([
                'message' => __('messages.notifications.declined'),
                'notification' => $notification
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => __('messages.notifications.declineFailed'),
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
                    'error' => __('messages.notifications.noSubscription')
                ], 404);
            }

            if ($request->input('disallow')) {
                $disallowedNotification = new NotificationDisallow();
                $disallowedNotification->email = $notification->email;
                $disallowedNotification->save();
            }

            $notification->delete();

            return response()->json([
                'message' => __('messages.notifications.unsubscribed'),
                'notification' => $notification
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => __('messages.notifications.unsubscribeFailed'),
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
                    'error' => __('messages.notifications.noSubscription')
                ], 404);
            }

            return response()->json([
                'message' => __('messages.notifications.subscriptionFound'),
                'notification' => $notification
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => __('messages.notifications.subscriptionFetchFailed'),
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
