<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workday extends Model
{
    protected $fillable = ['created_by', 'title', 'location', 'starts_at', 'notes'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}