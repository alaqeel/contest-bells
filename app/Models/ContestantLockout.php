<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestantLockout extends Model
{
    protected $fillable = [
        'contestant_id',
        'round_id',
        'locked_until',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    public function contestant(): BelongsTo
    {
        return $this->belongsTo(Contestant::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function isActive(): bool
    {
        return $this->locked_until->isFuture();
    }
}
