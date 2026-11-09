<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registration and approval (PRD 4.4, 4.12 users): accounts start as
     * "pending" and become "active" once Admin approves them. Existing
     * accounts stay active.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('email')->index();
            $table->string('phone', 30)->nullable()->after('email');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            // Last message from Admin: rejection reason or what data is missing.
            $table->text('review_note')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'phone', 'approved_at', 'review_note']);
        });
    }
};
