<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Short videos Admin uploads for the first segment of the homepage. They
     * play muted, one after another, in the hero frame.
     */
    public function up(): void
    {
        Schema::create('hero_videos', function (Blueprint $table) {
            $table->id();
            // Branch (PRD 4.10), like site_sections.
            $table->unsignedBigInteger('church_id')->nullable()->index();
            $table->string('title', 120)->nullable();
            $table->string('path');
            $table->string('poster_path')->nullable();
            $table->string('mime', 50);
            $table->unsignedBigInteger('size');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_videos');
    }
};
