<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $firstUserId = DB::table('users')->orderBy('id')->value('id');

        if ($firstUserId !== null) {
            DB::table('tasks')->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }
    }

    public function down(): void {}
};
