<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\WhatsAppTemplate;
use App\Services\Chat\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(int $conversationId): JsonResponse
    {
        $conversation = ChatConversation::with('phoneNumber.account')->findOrFail($conversationId);

        if (!$conversation->phoneNumber) {
            return response()->json(['error' => 'No WhatsApp phone number linked to this conversation'], 400);
        }

        $account = $conversation->phoneNumber->account;
        if (!$account) {
            return response()->json(['error' => 'No WhatsApp account found for this phone number'], 400);
        }

        $templates = WhatsAppTemplate::where('whatsapp_account_id', $account->id)
            ->approved()
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'language' => $t->language,
                'category' => $t->category,
                'status' => $t->status,
                'body' => collect($t->components)->firstWhere('type', 'BODY')['text'] ?? '',
                'components' => $t->components,
            ]);

        return response()->json($templates);
    }

    public function send(int $conversationId, Request $request, MessageService $messageService): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|integer',
            'params'       => 'sometimes|array',
            'params.*'     => 'array',
            'params.*.*'   => 'nullable|string',
        ]);

        if (!$request->user()->can('chat.reply')) {
            return response()->json(['message' => 'You do not have permission to reply.'], 403);
        }

        $conversation = ChatConversation::findOrFail($conversationId);
        $template = WhatsAppTemplate::findOrFail($request->template_id);

        $message = $messageService->sendTemplateMessage(
            $conversation,
            $template,
            $request->user(),
            $request->input('params', [])
        );

        return response()->json($message->load('sender:id,name'), 201);
    }
}
