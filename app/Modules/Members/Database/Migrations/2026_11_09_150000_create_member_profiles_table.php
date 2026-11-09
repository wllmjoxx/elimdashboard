<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Member profile (PRD 4.5, 4.12 member_profiles). One row per user,
     * created on first save. Families and groups attach to users in later
     * stages, so nothing here needs to change for them.
     */
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('nickname', 50)->nullable();
            // Path on the private "local" disk; served through an authorized route.
            $table->string('photo')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            // Encrypted by the model (sensitive personal data, UU PDP).
            $table->text('address')->nullable();
            $table->string('membership_status', 20)->default('sympathizer');
            $table->date('joined_at')->nullable();
            $table->boolean('baptized')->default(false);
            $table->json('privacy_settings')->nullable();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
