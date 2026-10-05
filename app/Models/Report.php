<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'rt_rw',
        'location',
        'latitude',
        'longitude',
        'density',
        'description',
        'deposit_weight',
        'photo_path',
        'status',
        'validation_status',
        'points_awarded',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForRtRw(Builder $query, string $rtRw): Builder
    {
        return $query->where(function (Builder $query) use ($rtRw): void {
            $query->where('rt_rw', $rtRw)
                ->orWhere(function (Builder $legacyQuery) use ($rtRw): void {
                    $legacyQuery->whereNull('rt_rw')
                        ->whereHas('user', fn (Builder $userQuery) => $userQuery->where('rt_rw', $rtRw));
                });
        });
    }
}
