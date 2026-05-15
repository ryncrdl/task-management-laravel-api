<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationJob extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id', 'user_id', 'event_type', 'details',
        'status', 'attempts', 'error_message',
        'scheduled_at', 'processed_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details'      => 'array',
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
            'created_at'   => 'datetime',
        ];
    }
}
