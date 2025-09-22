<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'whatsapp_login_code')) {
                    $table->string('whatsapp_login_code')->nullable();
                }
                if (!Schema::hasColumn('users', 'whatsapp_login_expires_at')) {
                    $table->timestamp('whatsapp_login_expires_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'whatsapp_login_code')) {
                    $table->dropColumn('whatsapp_login_code');
                }
                if (Schema::hasColumn('users', 'whatsapp_login_expires_at')) {
                    $table->dropColumn('whatsapp_login_expires_at');
                }
            });
        }
    }

};
