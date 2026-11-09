<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Branches (PRD 4.10, 4.12). Every core table carries a church_id that
     * points here; the first release only has GGP Elim.
     */
    public function up(): void
    {
        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            // Contact channels: phone, whatsapp, email.
            $table->json('contact')->nullable();
            $table->string('logo')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};
