<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = (int) $request->get('period', 30);
        $from = now()->subDays($days);

        $query = Customer::visibleTo($user);

        $total = (clone $query)->count();
        $newThisPeriod = (clone $query)->where('created_at', '>=', $from)->count();
        $converted = (clone $query)->where('status', 'converted')->count();
        $lost = (clone $query)->where('status', 'lost')->count();
        $conversionRate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        // Chat stats scoped to user visibility
        $chatQuery = ChatConversation::query();
        if (!$user->can('chat.view_all')) {
            $chatQuery->whereHas('customer', fn ($q) => $q->visibleTo($user));
        }

        $openChats = (clone $chatQuery)->where('status', 'open')->count();
        $pendingChats = (clone $chatQuery)->where('status', 'pending')->count();
        $unresolvedChats = (clone $chatQuery)->whereIn('status', ['open', 'pending'])->count();

        // Message stats
        $messageQuery = ChatMessage::where('created_at', '>=', $from);
        if (!$user->can('chat.view_all')) {
            $conversationIds = (clone $chatQuery)->pluck('id');
            $messageQuery->whereIn('conversation_id', $conversationIds);
        }

        $aiMessages = (clone $messageQuery)->where('is_ai_generated', true)->count();
        $agentMessages = (clone $messageQuery)->where('sender_type', 'agent')->count();
        $customerMessages = (clone $messageQuery)->where('sender_type', 'customer')->count();

        // Status breakdown (funnel)
        $statusBreakdown = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $funnel = [
            ['status' => 'inquiry', 'label' => 'Inquiry', 'count' => $statusBreakdown['inquiry'] ?? 0, 'color' => '#3b82f6'],
            ['status' => 'contacted', 'label' => 'Contacted', 'count' => $statusBreakdown['contacted'] ?? 0, 'color' => '#6366f1'],
            ['status' => 'qualified', 'label' => 'Qualified', 'count' => $statusBreakdown['qualified'] ?? 0, 'color' => '#8b5cf6'],
            ['status' => 'proposal', 'label' => 'Proposal', 'count' => $statusBreakdown['proposal'] ?? 0, 'color' => '#f59e0b'],
            ['status' => 'negotiation', 'label' => 'Negotiation', 'count' => $statusBreakdown['negotiation'] ?? 0, 'color' => '#f97316'],
            ['status' => 'converted', 'label' => 'Converted', 'count' => $statusBreakdown['converted'] ?? 0, 'color' => '#22c55e'],
        ];

        // Top agents (for managers)
        $topAgents = [];
        if ($user->can('customers.view_all') || $user->can('chat.view_all')) {
            $topAgents = Customer::visibleTo($user)
                ->select('assigned_to', DB::raw('count(*) as total'), DB::raw("SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_count"))
                ->whereNotNull('assigned_to')
                ->groupBy('assigned_to')
                ->with('assignedAgent:id,name')
                ->orderByDesc('converted_count')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'name' => $row->assignedAgent?->name ?? 'Unknown',
                    'total' => $row->total,
                    'converted' => $row->converted_count,
                    'rate' => $row->total > 0 ? round(($row->converted_count / $row->total) * 100, 1) : 0,
                ])
                ->toArray();
        }

        return response()->json([
            'stats' => [
                'total_customers' => $total,
                'new_this_period' => $newThisPeriod,
                'converted' => $converted,
                'lost' => $lost,
                'conversion_rate' => $conversionRate,
                'open_chats' => $openChats,
                'pending_chats' => $pendingChats,
                'unresolved_chats' => $unresolvedChats,
                'ai_messages' => $aiMessages,
                'agent_messages' => $agentMessages,
                'customer_messages' => $customerMessages,
            ],
            'funnel' => $funnel,
            'top_agents' => $topAgents,
            'period' => $days,
        ]);
    }
}
