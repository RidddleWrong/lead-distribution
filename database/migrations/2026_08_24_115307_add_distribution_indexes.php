<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index(['status', 'manager_id']);
        });

        Schema::table('lead_status_histories', function (Blueprint $table) {
            $table->index(['lead_id', 'to_status']);
        });
    }

    public function down(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status', 'manager_id']);
        });

        Schema::table('lead_status_histories', function (Blueprint $table) {
            $table->dropIndex(['lead_id', 'to_status']);
        });
    }
};
