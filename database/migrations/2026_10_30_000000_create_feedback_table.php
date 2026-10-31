<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 💬 Feedback: notes that visitors send about the website (ideas, problems, kind words).
     * The admin reads them in the dashboard and marks them "done" after improving the site.
     */
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            // who sent it (empty for guests; if the user is deleted, the note stays)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);              // idea / problem / praise
            $table->text('message');
            $table->string('email', 150)->nullable(); // optional, so the admin can reply to a guest
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
