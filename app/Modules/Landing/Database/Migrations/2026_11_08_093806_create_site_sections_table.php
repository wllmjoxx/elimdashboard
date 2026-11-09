<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Landing page sections (PRD 4.12 site_sections): order, visibility and
     * editable content per section.
     */
    public function up(): void
    {
        Schema::create('site_sections', function (Blueprint $table) {
            $table->id();
            // Branch (PRD 4.10). The foreign key to churches is added by the
            // Core module once that table exists.
            $table->unsignedBigInteger('church_id')->nullable()->index();
            $table->string('key', 50);
            $table->json('content');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['church_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sections');
    }
};
