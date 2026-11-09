<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prayer requests (PRD 4.6, 4.12 prayer_requests). Name, phone, address
     * and the request itself are encrypted by the model (UU PDP), so those
     * columns are text and cannot be searched in SQL.
     */
    public function up(): void
    {
        Schema::create('prayer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            // Empty for guests.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('full_name');
            $table->unsignedTinyInteger('age')->nullable();
            $table->text('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('body');
            $table->string('category', 30);
            // Monday of the week (WIB) the request belongs to.
            $table->date('week_start');
            $table->string('status', 20)->default('new');
            $table->foreignId('prayed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prayed_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'week_start', 'status']);
            $table->index(['user_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_requests');
    }
};
