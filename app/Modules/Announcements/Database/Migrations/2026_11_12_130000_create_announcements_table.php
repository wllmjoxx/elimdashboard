<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Church announcements (Pengumuman). Shown from published_on until
     * expires_on (inclusive); pinned ones come first.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_published')->default(true);
            $table->date('published_on');
            $table->date('expires_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['church_id', 'slug']);
            $table->index(['church_id', 'is_published', 'published_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
