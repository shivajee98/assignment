<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'title',
        'content',
        'visibility',
        'target_roles',
        'is_active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the sender of this announcement
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get users who have read this announcement
     */
    public function readBy()
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Check if announcement is visible to role
     */
    public function isVisibleToRole(string $role): bool
    {
        // Public announcements are visible to all
        if ($this->visibility === 'public') {
            return true;
        }

        // Check if role is in target roles
        if ($this->target_roles && in_array($role, $this->target_roles)) {
            return true;
        }

        return false;
    }

    /**
     * Check if announcement is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark as read by user
     */
    public function markAsRead(User $user)
    {
        if (!$this->readBy()->where('users.id', $user->id)->exists()) {
            $this->readBy()->attach($user->id, ['read_at' => now()]);
        }
    }

    /**
     * Check if read by user
     */
    public function isReadBy(User $user): bool
    {
        return $this->readBy()->where('users.id', $user->id)->exists();
    }

    /**
     * Scope for active announcements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for public announcements
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope for announcements visible to specific role
     */
    public function scopeForRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('visibility', 'public')
                ->orWhereJsonContains('target_roles', $role);
        });
    }
}
