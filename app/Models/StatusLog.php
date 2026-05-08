<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusLog extends Model
{
    use HasFactory;

    protected $table = 'status_logs';

    protected $fillable = [
        'request_id',
        'changed_by',
        'old_status',
        'new_status',
        'note'
    ];

    public function request()
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}