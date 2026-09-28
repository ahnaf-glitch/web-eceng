<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('location');
            $table->enum('density', ['ringan', 'sedang', 'parah']);
            $table->text('description');
            $table->string('photo_path')->nullable();
            $table->enum('status', ['baru', 'diproses', 'selesai'])->default('baru');
            $table->timestamps();
            $table->index(['status', 'density']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};