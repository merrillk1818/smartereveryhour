<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_number');
            $table->string('slack_webhook_url')->nullable()->after('timezone');
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('sms_enabled');
            $table->boolean('slack_enabled')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slack_webhook_url');
            $table->string('phone_number')->nullable()->after('timezone');
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('slack_enabled');
            $table->boolean('sms_enabled')->default(false)->after('is_active');
        });
    }
};
