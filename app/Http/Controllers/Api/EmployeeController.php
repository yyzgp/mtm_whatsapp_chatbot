<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? null,
                'phone' => $user->phone ?? null,
                'role' => $user->role ?? null,
            ],
        ]);
    }

    public function leaveTypes(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => LeaveType::active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function leaveBalances(Request $request): JsonResponse
    {
        $year = $request->get('year', now()->year);

        return response()->json([
            'status' => true,
            'data' => LeaveBalance::where('user_id', auth()->id())
                ->where('year', $year)
                ->with('leaveType')
                ->get(),
        ]);
    }

    public function leaveApplications(Request $request): JsonResponse
    {
        $query = LeaveApplication::where('user_id', auth()->id())
            ->with(['leaveType', 'approver']);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('year')) $query->whereYear('start_date', $request->year);

        return response()->json([
            'status' => true,
            'data' => $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)),
        ]);
    }

    public function applyLeave(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'leave_type_id' => 'required|integer',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_half' => 'in:full,first_half,second_half',
            'end_half' => 'in:full,first_half,second_half',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) return response()->json(['status' => false, 'errors' => $validator->errors()], 422);

        $leaveType = LeaveType::find($request->leave_type_id);
        if (!$leaveType || !$leaveType->is_active) {
            return response()->json(['status' => false, 'message' => 'Invalid or inactive leave type'], 400);
        }

        $totalDays = $this->calculateTotalDays(
            $request->start_date, $request->end_date,
            $request->get('start_half', 'full'), $request->get('end_half', 'full')
        );

        // Check balance
        $year = Carbon::parse($request->start_date)->year;
        $balance = LeaveBalance::where('user_id', auth()->id())
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $year)
            ->first();

        if (!$balance) return response()->json(['status' => false, 'message' => 'No leave balance found. Contact HR.'], 400);
        if ($totalDays > $balance->remaining) {
            return response()->json(['status' => false, 'message' => "Insufficient balance. Available: {$balance->remaining} days"], 400);
        }

        // Check overlap
        $overlap = LeaveApplication::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(fn($q2) => $q2->where('start_date', '<=', $request->start_date)->where('end_date', '>=', $request->end_date));
            })->exists();

        if ($overlap) return response()->json(['status' => false, 'message' => 'Overlapping leave application exists'], 400);

        DB::connection('shared')->beginTransaction();
        try {
            $application = LeaveApplication::create([
                'user_id' => auth()->id(),
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_days' => $totalDays,
                'start_half' => $request->get('start_half', 'full'),
                'end_half' => $request->get('end_half', 'full'),
                'reason' => $request->reason,
                'status' => LeaveApplication::STATUS_PENDING,
            ]);

            $balance->pending += $totalDays;
            $balance->save();

            DB::connection('shared')->commit();
            return response()->json(['status' => true, 'message' => 'Leave submitted', 'data' => $application->load('leaveType')], 201);
        } catch (\Exception $e) {
            DB::connection('shared')->rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to submit leave'], 500);
        }
    }

    public function cancelLeave($id): JsonResponse
    {
        $app = LeaveApplication::where('user_id', auth()->id())->find($id);
        if (!$app) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        if ($app->status !== LeaveApplication::STATUS_PENDING) {
            return response()->json(['status' => false, 'message' => 'Only pending leaves can be cancelled'], 400);
        }

        DB::connection('shared')->beginTransaction();
        try {
            $app->update(['status' => LeaveApplication::STATUS_CANCELLED, 'cancelled_at' => now()]);

            $balance = LeaveBalance::where('user_id', auth()->id())
                ->where('leave_type_id', $app->leave_type_id)
                ->where('year', $app->start_date->year)
                ->first();

            if ($balance) {
                $balance->pending = max(0, $balance->pending - $app->total_days);
                $balance->save();
            }

            DB::connection('shared')->commit();
            return response()->json(['status' => true, 'message' => 'Leave cancelled', 'data' => $app]);
        } catch (\Exception $e) {
            DB::connection('shared')->rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to cancel'], 500);
        }
    }

    public function clockIn(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $existing = AttendanceRecord::where('user_id', auth()->id())->where('date', $today)->first();

        if ($existing && $existing->time_in) {
            return response()->json(['status' => false, 'message' => 'Already clocked in today'], 400);
        }

        $now = now();
        $workStart = Carbon::today()->setHour(9)->setMinute(0);
        $lateMinutes = 0;
        $status = 'present';

        if ($now->greaterThan($workStart)) {
            $lateMinutes = $now->diffInMinutes($workStart);
            $status = 'late';
        }

        $record = AttendanceRecord::updateOrCreate(
            ['user_id' => auth()->id(), 'date' => $today],
            ['time_in' => $now, 'status' => $status, 'late_minutes' => $lateMinutes, 'ip_address' => $request->ip()]
        );

        return response()->json(['status' => true, 'message' => 'Clocked in', 'data' => $record]);
    }

    public function clockOut(): JsonResponse
    {
        $record = AttendanceRecord::where('user_id', auth()->id())->where('date', now()->toDateString())->first();

        if (!$record || !$record->time_in) return response()->json(['status' => false, 'message' => 'Not clocked in'], 400);
        if ($record->time_out) return response()->json(['status' => false, 'message' => 'Already clocked out'], 400);

        $now = now();
        $record->update([
            'time_out' => $now,
            'total_hours' => round($record->time_in->diffInMinutes($now) / 60, 2),
        ]);

        return response()->json(['status' => true, 'message' => 'Clocked out', 'data' => $record]);
    }

    public function todayAttendance(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => AttendanceRecord::where('user_id', auth()->id())->where('date', now()->toDateString())->first(),
        ]);
    }

    public function attendanceHistory(Request $request): JsonResponse
    {
        $records = AttendanceRecord::where('user_id', auth()->id())
            ->orderBy('date', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json(['status' => true, 'data' => $records]);
    }

    private function calculateTotalDays($startDate, $endDate, $startHalf, $endHalf): float
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->equalTo($end)) {
            return ($startHalf !== 'full' || $endHalf !== 'full') ? 0.5 : 1;
        }

        $days = $start->diffInDays($end) + 1;
        if ($startHalf === 'second_half') $days -= 0.5;
        if ($endHalf === 'first_half') $days -= 0.5;

        return $days;
    }
}
