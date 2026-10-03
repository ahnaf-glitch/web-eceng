<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRegistration extends Model
{
    protected $fillable = [
        'user_id',
        'activity',
        'group_name',
        'members',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'members' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
