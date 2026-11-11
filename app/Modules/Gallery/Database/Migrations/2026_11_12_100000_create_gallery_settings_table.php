<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the public gallery takes its photos from: albums uploaded here
     * ("manual") or the church's Instagram account ("instagram").
     */
    public function up(): void
    {
        Schema::create('gallery_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable()->unique();
            $table->string('source', 20)->default('manual');
            // Long-lived Instagram token, encrypted by the model.
            $table->text('instagram_token')->nullable();
            $table->string('instagram_user_id', 40)->nullable();
            $table->string('instagram_username', 60)->nullable();
            $table->timestamp('instagram_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_settings');
    }
};
