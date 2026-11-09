<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photo albums (PRD 4.8, 4.12 albums and album_photos).
     */
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('slug');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['church_id', 'slug']);
        });

        Schema::create('album_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            // Paths on the public disk: the compressed photo and a thumbnail.
            $table->string('image');
            $table->string('thumbnail');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('caption', 200)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['album_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_photos');
        Schema::dropIfExists('albums');
    }
};
