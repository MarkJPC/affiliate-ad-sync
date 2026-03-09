<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('ads', 'idx_ads_creative_type')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->index('creative_type', 'idx_ads_creative_type');
            });
        }

        if (! $this->indexExists('ads', 'idx_ads_last_synced_at')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->index('last_synced_at', 'idx_ads_last_synced_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('ads', 'idx_ads_creative_type')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->dropIndex('idx_ads_creative_type');
            });
        }

        if ($this->indexExists('ads', 'idx_ads_last_synced_at')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->dropIndex('idx_ads_last_synced_at');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'SELECT COUNT(*) AS count
                 FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND index_name = ?',
                [$table, $indexName]
            );

            return (int) ($row->count ?? 0) > 0;
        }

        // Safe default for other drivers used in development.
        return false;
    }
};
