<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sms_phone', 20)->nullable()->after('pushover_user_key');
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->boolean('sms_enabled')->default(false)->after('pushover_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sms_phone');
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('sms_enabled');
        });
    }
};
