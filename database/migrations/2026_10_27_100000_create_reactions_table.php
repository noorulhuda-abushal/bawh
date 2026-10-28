<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🤍 Reactions instead of likes: "I felt you" / "You're not alone" / "This touched me".
     * A user can give a quote each reaction once.
     */
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->string('type', 20); // felt | not_alone | touched
            $table->timestamps();

            // The same user can't give the same reaction to the same quote twice
            $table->unique(['user_id', 'quote_id', 'type']);
        });

        // The old likes become "I felt you 🤍", so nothing is lost
        if (Schema::hasTable('likes')) {
            foreach (DB::table('likes')->get() as $like) {
                DB::table('reactions')->insert([
                    'user_id' => $like->user_id,
                    'quote_id' => $like->quote_id,
                    'type' => 'felt',
                    'created_at' => $like->created_at,
                    'updated_at' => $like->updated_at,
                ]);
            }
            Schema::drop('likes');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
