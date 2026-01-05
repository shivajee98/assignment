<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Task::query()->with(['assigner:id,name,role', 'assignee:id,name,role']);

        if ($request->query('assigned_to_me')) {
            $query->where('assigned_to', $user->id);
        } elseif ($request->query('assigned_by_me')) {
            $query->where('assigned_by', $user->id);
        } else {
            $query->where(fn($q) => $q->where('assigned_to', $user->id)->orWhere('assigned_by', $user->id));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json(['success' => true, 'data' => $tasks->items(), 'meta' => ['total' => $tasks->total()]]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        $assignee = User::find($request->assigned_to);
        if (!$user->hasHigherAuthorityThan($assignee->role)) {
            return response()->json(['success' => false, 'message' => 'Cannot assign task to this user'], 403);
        }

        $task = Task::create([
            'assigned_by' => $user->id,
            'assigned_to' => $request->assigned_to,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'due_date' => $request->due_date,
            'group_id' => $request->group_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Task created', 'data' => $task], 201);
    }

    public function show(Task $task, Request $request)
    {
        $user = $request->user();
        if ($task->assigned_to !== $user->id && $task->assigned_by !== $user->id && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $task->load(['assigner:id,name,role', 'assignee:id,name,role']);
        return response()->json(['success' => true, 'data' => $task]);
    }

    public function update(Request $request, Task $task)
    {
        $user = $request->user();
        if ($task->assigned_by !== $user->id && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
        ]);

        $task->update($request->only(['title', 'description', 'priority', 'status', 'due_date']));
        if ($request->status === 'completed') {
            $task->update(['completed_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Task updated', 'data' => $task]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $user = $request->user();
        if ($task->assigned_to !== $user->id && $task->assigned_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['status' => 'required|in:pending,in_progress,completed,cancelled']);
        $task->update(['status' => $request->status]);
        if ($request->status === 'completed') {
            $task->update(['completed_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }

    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();
        if ($task->assigned_by !== $user->id && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $task->delete();
        return response()->json(['success' => true, 'message' => 'Task deleted']);
    }
}
