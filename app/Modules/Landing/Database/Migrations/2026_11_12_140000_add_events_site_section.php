<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sites seeded before the Events module get its homepage section right
     * after the hero. Fresh installs get it from LandingSeeder.
     */
    public function up(): void
    {
        $sections = DB::table('site_sections')->whereNull('church_id');

        if ((clone $sections)->doesntExist() || (clone $sections)->where('key', 'events')->exists()) {
            return;
        }

        DB::table('site_sections')->insert([
            'church_id' => null,
            'key' => 'events',
            'content' => json_encode([
                'title' => 'Kegiatan',
                'subtitle' => 'Yang sedang berjalan dan akan datang di GGP Elim.',
            ]),
            'is_active' => true,
            'sort_order' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_sections')->whereNull('church_id')->where('key', 'events')->delete();
    }
};
