<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructionAcknowledgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'instruction_id',
        'user_id',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    /**
     * Get the instruction
     */
    public function instruction()
    {
        return $this->belongsTo(Instruction::class);
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
