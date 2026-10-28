<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 💔 The new feelings + 🌙 anonymous bawh.
     * Old quotes get the new feeling that is closest to their old mood.
     */
    public function up(): void
    {
        // Old mood => new feeling
        $map = [
            'longing' => 'nostalgia',
            'hope' => 'hope',
            'love' => 'love',
            'calm' => 'relief',
            'passion' => 'love',
        ];
        foreach ($map as $old => $new) {
            DB::table('quotes')->where('mood', $old)->update(['mood' => $new]);
        }

        Schema::table('quotes', function (Blueprint $table) {
            // 🌙 true = the quote shows as "Anonymous bawh" without the writer's name
            $table->boolean('is_anonymous')->default(false)->after('mood');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });
    }
};
