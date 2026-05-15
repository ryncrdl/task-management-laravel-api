<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;

    protected $table = 'team_members';

    public const ROLE_MEMBER = 'member';
    public const ROLE_LEAD = 'lead';

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
