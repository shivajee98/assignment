<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marked_by',
        'date',
        'check_in',
        'check_out',
        'status',
        'remarks',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime:H:i:s',
            'check_out' => 'datetime:H:i:s',
        ];
    }

    /**
     * Get the user this attendance belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who marked this attendance
     */
    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Calculate working hours
     */
    public function getWorkingHoursAttribute(): ?float
    {
        if (!$this->check_in || !$this->check_out) {
            return null;
        }

        $checkIn = \Carbon\Carbon::parse($this->check_in);
        $checkOut = \Carbon\Carbon::parse($this->check_out);

        return round($checkOut->diffInMinutes($checkIn) / 60, 2);
    }

    /**
     * Scope for specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for present
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Scope for absent
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }
}
