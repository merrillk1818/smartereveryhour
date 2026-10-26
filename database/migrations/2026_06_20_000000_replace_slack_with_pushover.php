<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slack_webhook_url');
            $table->string('pushover_user_key', 30)->nullable()->after('timezone');
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('slack_enabled');
            $table->boolean('pushover_enabled')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pushover_user_key');
            $table->string('slack_webhook_url')->nullable()->after('timezone');
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('pushover_enabled');
            $table->boolean('slack_enabled')->default(false)->after('is_active');
        });
    }
};
