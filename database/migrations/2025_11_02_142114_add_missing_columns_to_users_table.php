<?php

// database/migrations/2025_11_02_000004_add_missing_columns_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add avatar column if it doesn't exist
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('email');
            }

            // Add is_online column if it doesn't exist (in case previous migration failed)
            if (! Schema::hasColumn('users', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('avatar');
            }

            // Add last_seen_at column if it doesn't exist (in case previous migration failed)
            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_online');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('users', 'avatar')) {
                $table->dropColumn('avatar');
            }
            if (Schema::hasColumn('users', 'is_online')) {
                $table->dropColumn('is_online');
            }
            if (Schema::hasColumn('users', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
        });
    }
};
