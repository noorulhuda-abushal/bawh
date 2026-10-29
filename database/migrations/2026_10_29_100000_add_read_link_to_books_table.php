<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 📖 Where the book can be read for free (legally), if anywhere.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('read_url', 500)->nullable()->after('verified');    // the free reading link
            $table->string('read_source', 50)->nullable()->after('read_url');  // e.g. Project Gutenberg
            $table->timestamp('read_checked_at')->nullable()->after('read_source'); // when we last looked
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['read_url', 'read_source', 'read_checked_at']);
        });
    }
};
