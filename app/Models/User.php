<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'rt_rw',
        'email',
        'password',
        'role',
        'points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function redemptions()
    {
        return $this->hasMany(Redemption::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getFormattedRtRwAttribute(): string
    {
        $rtRw = trim((string) $this->rt_rw);

        if (preg_match('/^(?:RT\\s*)?(\\d+)\\s*\\/\\s*(?:RW\\s*)?(\\d+)$/i', $rtRw, $matches)) {
            return 'RT '.$matches[1].' / RW '.$matches[2];
        }

        return $rtRw;
    }
}
