<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'phone_number')) {
                    $table->string('phone_number')->nullable();
                }
                $table->string('two_factor_type')->default('email');
                $table->boolean('is_2fa_required')->default(true);
                $table->string('sms_login_code')->nullable();
                $table->timestamp('sms_login_expires_at')->nullable();
                $table->string('two_factor_code')->nullable();
                $table->timestamp('two_factor_expires_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'phone_number',
                    'two_factor_type',
                    'is_2fa_required',
                    'sms_login_code',
                    'sms_login_expires_at',
                    'two_factor_code',
                    'two_factor_expires_at',
                ]);
            });
        }
    }
};
