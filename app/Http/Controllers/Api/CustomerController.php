<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('assignedAgent:id,name')
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'company_name', 'status', 'priority', 'assigned_to', 'updated_at')
            ->visibleTo($request->user())
            ->latest('updated_at');

        if ($request->search) {
            $query->search($request->search);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(30));
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['assignedAgent:id,name', 'source:id,name', 'tags:id,name,color'])
            ->findOrFail($id);

        return response()->json($customer);
    }

    public function updateStatus(int $id, Request $request): JsonResponse
    {
        if (!$request->user()->can('customers.edit')) {
            return response()->json(['message' => 'You do not have permission to edit customers.'], 403);
        }

        $request->validate([
            'status' => 'required|in:inquiry,contacted,qualified,proposal,negotiation,converted,lost,on_hold',
        ]);

        $customer = Customer::findOrFail($id);
        $oldStatus = $customer->status;
        $customer->update(['status' => $request->status]);

        // Log status change
        $customer->statusHistories()->create([
            'from_status' => $oldStatus,
            'to_status' => $request->status,
            'changed_by' => $request->user()->id,
            'notes' => $request->notes,
        ]);

        return response()->json($customer->fresh());
    }
}
