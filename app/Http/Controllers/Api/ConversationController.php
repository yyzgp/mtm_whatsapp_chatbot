<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chat\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = ChatConversation::with(['customer:id,first_name,last_name,status,priority', 'phoneNumber:id,name'])
            ->latest('last_message_at');

        // Phone number access filter
        $phoneIds = $user->getAccessiblePhoneNumberIds();
        if (!empty($phoneIds)) {
            $query->whereIn('whatsapp_phone_number_id', $phoneIds);
        }

        // Agent visibility filter
        if (!$user->can('chat.view_all')) {
            $visibleIds = array_unique(array_merge([$user->id], $user->getTeamMemberIds()));
            $query->where(function ($q) use ($visibleIds) {
                $q->whereHas('customer', fn($cq) => $cq->whereIn('assigned_to', $visibleIds));
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(30);

        return response()->json($conversations);
    }

    public function show(int $id): JsonResponse
    {
        $conversation = ChatConversation::with([
            'customer:id,first_name,last_name,status,priority,assigned_to,phone',
            'customer.assignedAgent:id,name',
            'phoneNumber:id,name',
        ])->findOrFail($id);

        $conversation->update(['unread_count' => 0]);

        return response()->json($conversation);
    }

    public function messages(int $id, Request $request): JsonResponse
    {
        $messages = ChatMessage::where('conversation_id', $id)
            ->with('sender:id,name')
            ->latest()
            ->paginate(50);

        // Resolve reply-to messages for any that have reply_to_wamid
        $replyWamids = collect($messages->items())
            ->pluck('reply_to_wamid')
            ->filter()
            ->unique()
            ->values();

        $replyMessages = [];
        if ($replyWamids->isNotEmpty()) {
            $replyMessages = ChatMessage::whereIn('wamid', $replyWamids)
                ->select('id', 'wamid', 'sender_type', 'content', 'message_type', 'media_caption')
                ->with('sender:id,name')
                ->get()
                ->keyBy('wamid');
        }

        $data = $messages->toArray();
        foreach ($data['data'] as &$msg) {
            $msg['reply_to_message'] = null;
            if (!empty($msg['reply_to_wamid']) && isset($replyMessages[$msg['reply_to_wamid']])) {
                $msg['reply_to_message'] = $replyMessages[$msg['reply_to_wamid']]->toArray();
            }
        }

        return response()->json($data);
    }

    public function sendMessage(int $id, Request $request, MessageService $messageService): JsonResponse
    {
        if (!$request->user()->can('chat.reply')) {
            return response()->json(['message' => 'You do not have permission to reply.'], 403);
        }

        $request->validate([
            'content' => 'required_without:media|nullable|string|max:4096',
            'media' => 'required_without:content|nullable|file|max:16384',
            'caption' => 'nullable|string|max:1024',
            'reply_to_wamid' => 'nullable|string|max:255',
        ]);

        $conversation = ChatConversation::findOrFail($id);

        if ($request->hasFile('media')) {
            $message = $messageService->sendAgentMedia(
                $conversation,
                $request->file('media'),
                $request->caption,
                $request->user()
            );
        } else {
            $message = $messageService->sendAgentMessage(
                $conversation,
                $request->content,
                $request->user(),
                $request->reply_to_wamid
            );
        }

        return response()->json($message->load('sender:id,name'), 201);
    }

    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $request->validate(['status' => 'required|in:open,pending,resolved,spam']);

        $conversation = ChatConversation::findOrFail($id);
        $conversation->update(['status' => $request->status]);

        return response()->json($conversation);
    }

    public function reactToMessage(int $conversationId, int $messageId, Request $request, MessageService $messageService): JsonResponse
    {
        if (!$request->user()->can('chat.reply')) {
            return response()->json(['message' => 'You do not have permission.'], 403);
        }

        $request->validate(['emoji' => 'required|string|max:20']);

        $message = ChatMessage::where('conversation_id', $conversationId)->findOrFail($messageId);
        $messageService->sendAgentReaction($message, $request->emoji);

        return response()->json(['reaction' => $message->fresh()->reaction]);
    }

    public function toggleAi(int $id, Request $request): JsonResponse
    {
        if (!$request->user()->can('chat.manage_ai')) {
            return response()->json(['message' => 'You do not have permission to manage AI.'], 403);
        }

        $conversation = ChatConversation::findOrFail($id);

        if ($conversation->ai_active) {
            $conversation->update(['ai_active' => false]);
        } else {
            $conversation->update(['ai_active' => true, 'ai_paused_until' => null]);
        }

        return response()->json(['ai_active' => $conversation->fresh()->ai_active]);
    }
}
