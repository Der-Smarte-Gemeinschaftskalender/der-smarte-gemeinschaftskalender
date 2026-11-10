<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Validator;
use Log;
use Throwable;

class EmailTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', 'is_admin'];
    }

    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        try {
            return response()->json($emailTemplate);
        } catch (Throwable $e) {
            Log::error("Error showing email template: " . $e->getMessage());
            return response()->json(['error' => __('messages.emailTemplates.notFound')], 404);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $page = max($page, 1);
        $pageSize = max($pageSize, 1);

        $data = EmailTemplate::orderBy('on_event')->orderBy('locale')->paginate(
            $pageSize,
            ['id', 'on_event', 'locale', 'subject'],
            'page',
            $page
        );

        return response()->json([
            'data' => $data->items(),
            'total' => $data->total(),
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
        
    }

    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:70',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'errors' => $errors
            ], 422);
        }

        $emailTemplate->update([
            'subject' => $validator->validated()['subject'],
            'body' => $validator->validated()['body'],
        ]);

        return response()->json($emailTemplate);
    }
}
