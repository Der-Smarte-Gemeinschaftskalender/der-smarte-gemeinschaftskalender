<?php

namespace App\Http\Controllers;

use App\Models\Mobilizon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\User;
use Log;
use rdx\graphqlquery\Query;
use Str;
use Mail;
use App\Mail\SendConfirmEmail;
use App\Mail\SendInfoUserDeletedEmail;

class AdminController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return ['auth', 'is_admin'];
    }


    public function user_index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 15);

        $page = max($page, 1);
        $pageSize = max($pageSize, 1);

        $query = User::query()
            ->where('id', '<>', auth()->user()->id)
            ->orderBy('id', 'desc');
        $data = $query->paginate(
            $pageSize,
            ['id', 'mobilizon_name', 'mobilizon_preferred_username', 'email', 'type', 'is_active'],
            'page',
            $page
        );

        return response()->json([
            'data' => $data->items(),
            'total' => $data->total() - 1,
            'page' => $page,
            'pageSize' => $pageSize
        ]);
    }

    public function export_user_emails(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['email']);

            User::query()
                ->where('id', '<>', auth()->id())
                ->where('is_active', true)
                ->orderBy('id')
                ->select('email')
                ->chunk(1000, function ($users) use ($file) {
                    foreach ($users as $user) {
                        fputcsv($file, [$user->email]);
                    }
                });

            fclose($file);
        }, 'validated-emails.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show_user(User $user): User
    {
        return $user;
    }

    public function create_user(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'type' => 'required',
            'password' => 'required',
        ]);

        $type = $request->type;

        $mobilizon_password = Str::random(32);
        $mclient = Mobilizon::getInstance(true);
        $createdMobilizonUser = $mclient->createUser($request->email, $mobilizon_password);

        $user = new User();
        $user->email = $request->email;
        $user->type = $type;
        $user->password = bcrypt($request->password);
        $user->mobilizon_email = $request->email;
        $user->mobilizon_password = $mobilizon_password;
        $user->is_active = false;
        $user->mobilizon_user_id = $createdMobilizonUser['data']['createUser']['id'];
        $user->save();

        if ($type === 'admin') {
            $systemAdmin = User::find(1);
            $adminClient = Mobilizon::getInstance(false, $systemAdmin, true);

            $mresponse = $adminClient->adminUpdateUser([
                'id' => $user->mobilizon_user_id,
                'confirmed' => true,
            ]);

            if ($mclient->hasError($mresponse)) {
                Log::error('Validierung des neuen Benutzers fehlgeschlagen', [
                    'error' => $mclient->getError($mresponse),
                    'user_id' => $user->id
                ]);
                return response()->json(['error' => 'Validierung des neuen Benutzers fehlgeschlagen'], 500);
            }
            $mresponseChangeRole = $adminClient->adminUpdateUser([
                'id' => $user->mobilizon_user_id,
                'role' => Query::enum('ADMINISTRATOR')
            ]);

            if ($mclient->hasError($mresponseChangeRole)) {
                Log::error('Validierung des neuen Benutzers fehlgeschlagen', [
                    'error' => $mclient->getError($mresponseChangeRole),
                    'user_id' => $user->id
                ]);
                return response()->json(['error' => 'Rollen veränderung des neuen Benutzers fehlgeschlagen'], 500);
            }
            $user->is_active = true;
            $user->save();
        } else {
            $user->email_verification_token = Str::uuid()->toString();
            $user->save();
            Mail::to($user->email)
                ->send(new SendConfirmEmail($user->email_verification_token));
        }

        return response()->json([
            'message' => 'Erfolgreich erstellt',
            'id' => $user->id
        ], 201);
    }

    public function update_user(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'type' => 'required',
            'password' => 'nullable',
            'is_active' => 'required'
        ]);

        $user->email = $request->email;
        $user->type = $request->type;

        if ($request->password) {
            $user->password = bcrypt($request->password);
        }

        if ($user->is_active !== $request->is_active) {
            $systemAdmin = User::find(1);
            $adminClient = Mobilizon::getInstance(false, $systemAdmin, true);

            $mresponse = $adminClient->adminUpdateUser([
                'id' => $user->mobilizon_user_id,
                'confirmed' => $request->is_active,
            ]);
        }

        $user->is_active = $request->is_active;
        $user->save();

        return response()->json([
            'message' => 'Nutzer erfolgreich aktualisiert',
            'user' => $user
        ]);
    }

    public function delete_user(User $user): JsonResponse
    {
        try {

            $mclient = Mobilizon::getInstance();
            $mresponse = $mclient->deleteAccount($user->mobilizon_user_id, $user->mobilizon_password);

            $user->delete();

            try {
                Mail::to($user->email)
                    ->send(new SendInfoUserDeletedEmail());
            } catch (\Exception $e) {
                Log::error('Fehler beim Email Senden Löschen des Mobilizon Benutzers: ' . $e->getMessage());
            }
            return response()->json([
                'username' => $mresponse['data']['deleteAccount']['id'],
                'message' => 'Nutzer erfolgreich gelöscht',
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Fehler beim Löschen des Benutzers',
                [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]
            );
            return response()->json(['error' => 'Fehler beim Löschen des Benutzers'], 500);
        }
    }

    public function mobilizonAccessData(): JsonResponse
    {
        $user = User::find(auth()->user()->id);
        $user->setHidden([])->setVisible(['mobilizon_password', 'mobilizon_email']);
        return response()->json($user);
    }
}
