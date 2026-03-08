<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuzzAttempt extends Model
{
    protected $fillable = [
        'round_id',
        'contestant_id',
        'attempted_at',
        'accepted',
        'rejection_reason',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'accepted'     => 'boolean',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function contestant(): BelongsTo
    {
        return $this->belongsTo(Contestant::class);
    }
}
