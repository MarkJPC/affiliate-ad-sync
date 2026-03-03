<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->index('creative_type', 'idx_ads_creative_type');
            $table->index('last_synced_at', 'idx_ads_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('idx_ads_creative_type');
            $table->dropIndex('idx_ads_last_synced_at');
        });
    }
};
