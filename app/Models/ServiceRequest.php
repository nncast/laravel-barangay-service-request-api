<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'tracking_code',
        'user_id',
        'category_id',
        'title',
        'description',
        'priority',
        'status',
        'assigned_to',
        'remarks',
        'scheduled_at',
        'completed_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function logs()
    {
        return $this->hasMany(StatusLog::class, 'request_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'request_id');
    }

    /**
     * Helper methods
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'in_review']);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => '#F59E0B',
            'in_review' => '#3B82F6',
            'approved' => '#10B981',
            'processing' => '#8B5CF6',
            'completed' => '#14B8A6',
            'rejected' => '#EF4444',
            'cancelled' => '#6B7280',
            default => '#6B7280',
        };
    }
}