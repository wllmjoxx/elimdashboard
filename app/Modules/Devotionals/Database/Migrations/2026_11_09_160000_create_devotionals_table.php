<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily devotionals (PRD 4.8, 4.12 devotionals): Pelayan writes drafts,
     * Admin publishes them on a date (today or scheduled).
     */
    public function up(): void
    {
        Schema::create('devotionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('scripture_ref', 100);
            // The verse itself, quoted at the top of the devotional.
            $table->text('scripture_text')->nullable();
            $table->text('body');
            $table->date('publish_date');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['church_id', 'slug']);
            $table->index(['church_id', 'status', 'publish_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devotionals');
    }
};
