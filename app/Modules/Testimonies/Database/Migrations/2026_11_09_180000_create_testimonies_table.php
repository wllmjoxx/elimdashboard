<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anonymous testimonies (PRD 4.7, 4.12 testimonies). There is deliberately
     * no user_id, IP or other sender data: nobody, Admin included, can tell
     * who sent a testimony.
     */
    public function up(): void
    {
        Schema::create('testimonies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->string('category', 30);
            // Optional name to show, chosen by the sender (may be a pseudonym).
            $table->string('display_name', 60)->nullable();
            $table->string('status', 20)->default('pending');
            // Shown in the homepage carousel ("Kesaksian terpilih").
            $table->boolean('is_featured')->default(false);
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            // Internal only, never shown to visitors.
            $table->text('moderation_note')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'status', 'moderated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonies');
    }
};
