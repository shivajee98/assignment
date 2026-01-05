<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    private $roleHierarchy = [
        'super_admin' => 1,
        'admin' => 2,
        'manager' => 3,
        'incharge' => 4,
        'team_leader' => 5,
        'employee' => 6
    ];

    public function getConversations()
    {
        $user = Auth::user();
        $userLevel = $this->roleHierarchy[$user->role] ?? 6;
        
        $messages = Message::where('type', 'private')
            ->where(function($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $conversations = [];
        $processed = [];
        
        foreach ($messages as $msg) {
            $otherUser = $msg->sender_id == $user->id ? $msg->receiver : $msg->sender;
            if (!$otherUser || in_array($otherUser->id, $processed)) continue;
            
            $otherLevel = $this->roleHierarchy[$otherUser->role] ?? 6;
            
            if ($userLevel <= $otherLevel) {
                $processed[] = $otherUser->id;
                $conversations[] = [
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'role' => $otherUser->role
                    ],
                    'last_message' => [
                        'content' => $msg->content,
                        'created_at' => $msg->created_at,
                        'is_from_me' => $msg->sender_id == $user->id
                    ],
                    'unread_count' => 0
                ];
            }
        }
        
        return response()->json(['success' => true, 'data' => $conversations]);
    }

    public function getPrivateMessages($userId)
    {
        $user = Auth::user();
        $otherUser = User::find($userId);
        
        if (!$otherUser) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        
        $userLevel = $this->roleHierarchy[$user->role] ?? 6;
        $otherLevel = $this->roleHierarchy[$otherUser->role] ?? 6;
        
        if ($userLevel > $otherLevel) {
            return response()->json(['success' => false, 'message' => 'Cannot view messages from higher level'], 403);
        }
        
        $messages = Message::where('type', 'private')
            ->where(function($q) use ($user, $userId) {
                $q->where(function($q2) use ($user, $userId) {
                    $q2->where('sender_id', $user->id)->where('receiver_id', $userId);
                })->orWhere(function($q2) use ($user, $userId) {
                    $q2->where('sender_id', $userId)->where('receiver_id', $user->id);
                });
            })
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'content' => $msg->content,
                    'sender' => $msg->sender ? ['id' => $msg->sender->id, 'name' => $msg->sender->name, 'role' => $msg->sender->role] : null,
                    'is_from_me' => $msg->sender_id == $user->id,
                    'created_at' => $msg->created_at
                ];
            });
        
        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function sendPrivateMessage(Request $request, $userId)
    {
        $request->validate(['content' => 'required|string']);
        
        $user = Auth::user();
        $receiver = User::find($userId);
        
        if (!$receiver) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        
        $userLevel = $this->roleHierarchy[$user->role] ?? 6;
        $receiverLevel = $this->roleHierarchy[$receiver->role] ?? 6;
        
        if ($userLevel > $receiverLevel) {
            return response()->json(['success' => false, 'message' => 'Cannot send message to higher level'], 403);
        }
        
        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'type' => 'private',
            'content' => $request->content
        ]);
        
        return response()->json(['success' => true, 'message' => 'Message sent', 'data' => $message]);
    }

    public function getGroupMessages($groupId)
    {
        $user = Auth::user();
        $group = Group::find($groupId);
        
        if (!$group || !$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }
        
        $messages = Message::where('type', 'group')
            ->where('group_id', $groupId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'content' => $msg->content,
                    'sender' => $msg->sender ? ['id' => $msg->sender->id, 'name' => $msg->sender->name, 'role' => $msg->sender->role] : null,
                    'is_from_me' => $msg->sender_id == $user->id,
                    'created_at' => $msg->created_at
                ];
            });
        
        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function sendGroupMessage(Request $request, $groupId)
    {
        $request->validate(['content' => 'required|string']);
        
        $user = Auth::user();
        $group = Group::find($groupId);
        
        if (!$group || !$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }
        
        $message = Message::create([
            'sender_id' => $user->id,
            'group_id' => $group->id,
            'type' => 'group',
            'content' => $request->content
        ]);
        
        return response()->json(['success' => true, 'message' => 'Message sent', 'data' => $message]);
    }

    public function broadcast(Request $request)
    {
        $request->validate(['content' => 'required|string']);
        
        $user = Auth::user();
        $userLevel = $this->roleHierarchy[$user->role] ?? 6;
        
        $recipients = User::where('id', '!=', $user->id)
            ->get()
            ->filter(function($u) use ($userLevel) {
                $level = $this->roleHierarchy[$u->role] ?? 6;
                return $level > $userLevel;
            });
        
        $messages = [];
        foreach ($recipients as $recipient) {
            $messages[] = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $recipient->id,
                'type' => 'private',
                'content' => $request->content
            ]);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'Broadcast sent to ' . count($messages) . ' users'
        ]);
    }

    public function getMessageableUsers()
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
                return ['id' => $u->id, 'name' => $u->name, 'role' => $u->role];
            })
            ->values();
        
        return response()->json(['success' => true, 'data' => $users]);
    }
}
