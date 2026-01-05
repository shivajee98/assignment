<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Attendance::query()->with(['user:id,name,role', 'marker:id,name,role']);

        if ($request->query('user_id') && $user->hasHigherAuthorityThan(User::find($request->query('user_id'))?->role ?? 'employee')) {
            $query->where('user_id', $request->query('user_id'));
        } elseif (!$user->hasRole('super_admin') && !$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        if ($date = $request->query('date')) {
            $query->whereDate('date', $date);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('date', '<=', $to);
        }

        $records = $query->orderBy('date', 'desc')->paginate(30);
        return response()->json(['success' => true, 'data' => $records->items(), 'meta' => ['total' => $records->total()]]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,half_day,leave',
            'remarks' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $targetUser = User::find($request->user_id);
        if (!$user->hasHigherAuthorityThan($targetUser->role) && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Cannot mark attendance for this user'], 403);
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            [
                'marked_by' => $user->id,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'status' => $request->status,
                'remarks' => $request->remarks,
                'location' => $request->location,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Attendance recorded', 'data' => $attendance], 201);
    }

    public function show(Request $request, Attendance $attendance)
    {
        $user = $request->user();
        if ($attendance->user_id !== $user->id && !$user->hasHigherAuthorityThan($attendance->user->role)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $attendance->load(['user:id,name,role', 'marker:id,name,role']);
        return response()->json(['success' => true, 'data' => $attendance]);
    }

    public function myAttendance(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $records = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        $summary = [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'half_day' => $records->where('status', 'half_day')->count(),
            'leave' => $records->where('status', 'leave')->count(),
        ];

        return response()->json(['success' => true, 'data' => $records, 'summary' => $summary]);
    }

    public function teamAttendance(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', date('Y-m-d'));

        $subordinateIds = $user->subordinates()->pluck('id');
        $records = Attendance::whereIn('user_id', $subordinateIds)
            ->whereDate('date', $date)
            ->with('user:id,name,role')
            ->get();

        return response()->json(['success' => true, 'data' => $records]);
    }
}
