<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('canvas_user_id')->nullable()->after('email');
            $table->string('canvas_user_name')->nullable()->after('canvas_user_id');
            $table->text('canvas_access_token')->nullable()->after('canvas_user_name');
            $table->text('canvas_refresh_token')->nullable()->after('canvas_access_token');
            $table->timestamp('canvas_token_expires_at')->nullable()->after('canvas_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'canvas_user_id',
                'canvas_user_name',
                'canvas_access_token',
                'canvas_refresh_token',
                'canvas_token_expires_at',
            ]);
        });
    }
};
