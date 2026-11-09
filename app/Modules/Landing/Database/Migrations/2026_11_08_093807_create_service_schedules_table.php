<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Worship and activity schedule (PRD 4.12 service_schedules).
     */
    public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id();
            // Branch (PRD 4.10); foreign key added by the Core module.
            $table->unsignedBigInteger('church_id')->nullable()->index();
            // raya, komisi or doa (see ServiceSchedule::CATEGORIES).
            $table->string('category', 20);
            $table->string('name');
            // 0 = Minggu ... 6 = Sabtu
            $table->unsignedTinyInteger('day');
            $table->time('start_time');
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
