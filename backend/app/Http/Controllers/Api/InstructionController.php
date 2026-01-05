<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instruction;
use App\Models\Group;
use Illuminate\Http\Request;

class InstructionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $groupIds = $user->groups()->pluck('groups.id');

        $instructions = Instruction::whereIn('group_id', $groupIds)
            ->orWhere('sent_by', $user->id)
            ->with(['sender:id,name,role', 'group:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $instructions->map(fn($i) => $this->format($i, $user)),
            'meta' => ['total' => $instructions->total()],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|in:normal,important,urgent',
            'requires_acknowledgment' => 'nullable|boolean',
        ]);

        $group = Group::find($request->group_id);
        if ($group->created_by !== $user->id && !$group->isAdmin($user)) {
            return response()->json(['success' => false, 'message' => 'Only group admin can send instructions'], 403);
        }

        $instruction = Instruction::create([
            'sent_by' => $user->id,
            'group_id' => $request->group_id,
            'title' => $request->title,
            'content' => $request->content,
            'priority' => $request->priority ?? 'normal',
            'requires_acknowledgment' => $request->requires_acknowledgment ?? false,
        ]);

        return response()->json(['success' => true, 'message' => 'Instruction sent', 'data' => $this->format($instruction, $user)], 201);
    }

    public function show(Request $request, Instruction $instruction)
    {
        $user = $request->user();
        $instruction->load(['sender:id,name,role', 'group:id,name', 'acknowledgedBy:id,name']);
        return response()->json(['success' => true, 'data' => $this->format($instruction, $user)]);
    }

    public function acknowledge(Request $request, Instruction $instruction)
    {
        $user = $request->user();
        $instruction->acknowledge($user);
        return response()->json(['success' => true, 'message' => 'Instruction acknowledged']);
    }

    public function acknowledgments(Request $request, Instruction $instruction)
    {
        $user = $request->user();
        if ($instruction->sent_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $acks = $instruction->acknowledgedBy()->get(['users.id', 'users.name', 'users.role']);
        return response()->json([
            'success' => true,
            'data' => $acks,
            'stats' => [
                'acknowledged' => $acks->count(),
                'total_members' => $instruction->group?->members()->count() ?? 0,
                'rate' => $instruction->acknowledgment_rate,
            ],
        ]);
    }

    public function destroy(Request $request, Instruction $instruction)
    {
        $user = $request->user();
        if ($instruction->sent_by !== $user->id && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $instruction->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    private function format(Instruction $i, $user): array
    {
        return [
            'id' => $i->id, 'title' => $i->title, 'content' => $i->content,
            'priority' => $i->priority, 'requires_acknowledgment' => $i->requires_acknowledgment,
            'sender' => $i->sender ? ['id' => $i->sender->id, 'name' => $i->sender->name, 'role' => $i->sender->role] : null,
            'group' => $i->group ? ['id' => $i->group->id, 'name' => $i->group->name] : null,
            'is_acknowledged' => $i->isAcknowledgedBy($user),
            'acknowledgment_rate' => $i->acknowledgment_rate,
            'created_at' => $i->created_at->toISOString(),
        ];
    }
}
