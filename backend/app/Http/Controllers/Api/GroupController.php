<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $groups = $user->groups()->get()->map(function($g) {
            return [
                'id' => $g->id,
                'name' => $g->name,
                'description' => $g->description,
                'group_type' => $g->group_type,
                'member_count' => $g->members()->count()
            ];
        });
        
        return response()->json(['success' => true, 'data' => $groups]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'group_type' => 'required|string'
        ]);

        $user = Auth::user();

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'group_type' => $request->group_type,
            'created_by' => $user->id
        ]);

        $group->members()->attach($user->id, ['is_admin' => true]);

        return response()->json(['success' => true, 'message' => 'Group created', 'data' => $group]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $group = Group::find($id);
        
        if (!$group || !$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'group_type' => $group->group_type,
                'member_count' => $group->members()->count(),
                'members' => $group->members->map(function($m) {
                    return ['id' => $m->id, 'name' => $m->name, 'role' => $m->role];
                })
            ]
        ]);
    }
}
