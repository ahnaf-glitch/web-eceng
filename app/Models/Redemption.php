<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redemption extends Model
{
    public const REWARDS = [
        'e-sertifikat' => ['label' => 'E-Sertifikat Kontributor', 'points' => 50],
        'merchandise' => ['label' => 'Merchandise sederhana', 'points' => 100],
        'voucher-umkm' => ['label' => 'Voucher produk UMKM', 'points' => 200],
        'kerajinan-eceng-gondok' => ['label' => 'Produk kerajinan eceng gondok', 'points' => 300],
        'insentif' => ['label' => 'Voucher/insentif tertentu', 'points' => 500],
        'penghargaan' => ['label' => 'Penghargaan Kontributor Lingkungan', 'points' => 1000],
    ];

    protected $fillable = ['user_id', 'reward', 'provider', 'points', 'destination', 'status'];

    public function rewardLabel(): string
    {
        return self::REWARDS[$this->reward]['label'] ?? ucfirst($this->reward);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
