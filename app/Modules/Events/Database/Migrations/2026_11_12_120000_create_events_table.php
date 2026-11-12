<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Church events shown on the homepage and /kegiatan. Times are stored as
     * local church time (config modules.events.timezone).
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('summary', 300)->nullable();
            $table->text('body')->nullable();
            $table->string('location', 160)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('image_path')->nullable();
            $table->string('registration_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['church_id', 'slug']);
            $table->index(['church_id', 'is_published', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
