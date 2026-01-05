<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $announcements = Announcement::active()
            ->forRole($currentUser->role)
            ->with('sender:id,name,role')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $announcements->map(fn($a) => $this->format($a, $currentUser)),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $currentUser = $request->user();
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'visibility' => 'required|in:public,private',
            'target_roles' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($request->visibility === 'public' && !$currentUser->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Only Super Admin can create public announcements'], 403);
        }

        $announcement = Announcement::create([
            'sender_id' => $currentUser->id,
            'title' => $request->title,
            'content' => $request->content,
            'visibility' => $request->visibility,
            'target_roles' => $request->target_roles,
            'expires_at' => $request->expires_at,
        ]);

        return response()->json(['success' => true, 'message' => 'Announcement created', 'data' => $this->format($announcement, $currentUser)], 201);
    }

    public function show(Request $request, Announcement $announcement)
    {
        $currentUser = $request->user();
        $announcement->markAsRead($currentUser);
        return response()->json(['success' => true, 'data' => $this->format($announcement, $currentUser)]);
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $currentUser = $request->user();
        if ($announcement->sender_id !== $currentUser->id && !$currentUser->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $announcement->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    public function unreadCount(Request $request)
    {
        $currentUser = $request->user();
        $count = Announcement::active()->forRole($currentUser->role)
            ->whereDoesntHave('readBy', fn($q) => $q->where('users.id', $currentUser->id))->count();
        return response()->json(['success' => true, 'data' => ['unread_count' => $count]]);
    }

    private function format(Announcement $a, $user): array
    {
        return [
            'id' => $a->id, 'title' => $a->title, 'content' => $a->content,
            'visibility' => $a->visibility, 'target_roles' => $a->target_roles,
            'sender' => $a->sender ? ['id' => $a->sender->id, 'name' => $a->sender->name, 'role' => $a->sender->role] : null,
            'is_read' => $a->isReadBy($user), 'created_at' => $a->created_at->toISOString(),
        ];
    }
}
