<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 📚 Books: where a quote comes from (optional).
     * The AI checks that the book is real and finds its author (verified = true).
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('author', 100)->nullable();
            $table->string('slug', 255)->unique(); // the title + author written in one simple way, so a book is saved only once
            $table->boolean('verified')->default(false); // ✨ true = the AI confirmed the book exists
            $table->timestamps();
        });

        Schema::table('quotes', function (Blueprint $table) {
            // A quote can have a book or not. If the book is deleted, the quote stays (book_id becomes empty)
            $table->foreignId('book_id')->nullable()->after('user_id')->constrained('books')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('book_id');
        });
        Schema::dropIfExists('books');
    }
};
