<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Convenience factory: log an activity for the currently authenticated user.
     */
    public static function record(
        string $action,
        string $subjectType,
        int $subjectId,
        ?string $subjectName = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): self {
        return static::create([
            'user_id'      => auth('api')->id(),
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'subject_name' => $subjectName,
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
            'created_at'   => now(),
        ]);
    }
}
