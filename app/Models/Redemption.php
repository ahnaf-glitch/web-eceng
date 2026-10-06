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
    ];

    protected $fillable = ['user_id', 'reward', 'provider', 'points', 'phone', 'status'];

    public function rewardLabel(): string
    {
        return self::REWARDS[$this->reward]['label'] ?? ucfirst($this->reward);
    }

    public function whatsappUrl(): ?string
    {
        $phone = preg_replace('/[\s().-]+/', '', $this->phone);
        if (! is_string($phone) || ! preg_match('/^(?:\+?62|0)?8\d{7,12}$/', $phone)) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62'.$phone;
        } elseif (! str_starts_with($phone, '62')) {
            return null;
        }

        $message = "Halo {$this->user->name}, hadiah {$this->rewardLabel()} dari program Warga Peduli sudah siap diambil. Silakan datang ke kantor Kelurahan untuk mengambilnya. Terima kasih.";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
