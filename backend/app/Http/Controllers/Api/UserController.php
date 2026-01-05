<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private $roleHierarchy = [
        'super_admin' => 1,
        'admin' => 2,
        'manager' => 3,
        'incharge' => 4,
        'team_leader' => 5,
        'employee' => 6
    ];

    public function index()
    {
        $user = Auth::user();
        $userLevel = $this->roleHierarchy[$user->role] ?? 6;
        
        $users = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->get()
            ->filter(function($u) use ($userLevel) {
                $level = $this->roleHierarchy[$u->role] ?? 6;
                return $level >= $userLevel;
            })
            ->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'is_active' => $u->is_active
                ];
            })
            ->values();
        
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,manager,incharge,team_leader,employee'
        ]);

        $creator = Auth::user();
        $creatorLevel = $this->roleHierarchy[$creator->role] ?? 6;
        $newUserLevel = $this->roleHierarchy[$request->role] ?? 6;
        
        if ($creatorLevel >= $newUserLevel) {
            return response()->json(['success' => false, 'message' => 'Cannot create user at this level'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'parent_id' => $creator->id,
            'is_active' => true
        ]);

        return response()->json(['success' => true, 'message' => 'User created', 'data' => $user]);
    }
}
