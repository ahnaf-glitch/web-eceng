<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('rt_rw')->nullable()->after('user_id');
            $table->unsignedInteger('deposit_weight')->nullable()->after('description');
            $table->string('validation_status')->default('menunggu')->after('status');
            $table->unsignedInteger('points_awarded')->default(0)->after('validation_status');
            $table->index(['rt_rw', 'validation_status']);
        });

        DB::table('reports')->update([
            'validation_status' => 'valid',
            'points_awarded' => 10,
        ]);
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['rt_rw', 'validation_status']);
            $table->dropColumn(['rt_rw', 'deposit_weight', 'validation_status', 'points_awarded']);
        });
    }
};
