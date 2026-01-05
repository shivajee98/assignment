<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instruction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sent_by',
        'group_id',
        'title',
        'content',
        'priority',
        'requires_acknowledgment',
    ];

    protected function casts(): array
    {
        return [
            'requires_acknowledgment' => 'boolean',
        ];
    }

    /**
     * Get the user who sent this instruction
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get the group this instruction is for
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get acknowledgments for this instruction
     */
    public function acknowledgments()
    {
        return $this->hasMany(InstructionAcknowledgment::class);
    }

    /**
     * Get users who acknowledged this instruction
     */
    public function acknowledgedBy()
    {
        return $this->belongsToMany(User::class, 'instruction_acknowledgments')
            ->withPivot('acknowledged_at')
            ->withTimestamps();
    }

    /**
     * Acknowledge instruction by user
     */
    public function acknowledge(User $user)
    {
        if (!$this->acknowledgedBy()->where('users.id', $user->id)->exists()) {
            $this->acknowledgedBy()->attach($user->id, [
                'acknowledged_at' => now(),
            ]);
        }
    }

    /**
     * Check if acknowledged by user
     */
    public function isAcknowledgedBy(User $user): bool
    {
        return $this->acknowledgedBy()->where('users.id', $user->id)->exists();
    }

    /**
     * Get acknowledgment rate
     */
    public function getAcknowledgmentRateAttribute(): float
    {
        if (!$this->group) {
            return 0;
        }

        $totalMembers = $this->group->members()->count();
        if ($totalMembers === 0) {
            return 0;
        }

        $acknowledged = $this->acknowledgedBy()->count();
        return round(($acknowledged / $totalMembers) * 100, 2);
    }

    /**
     * Scope for urgent instructions
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }
}
