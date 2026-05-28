<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Set the electronics theme on all channels and customizations.
     */
    public function up(): void
    {
        // Set all channels to use the electronics theme
        DB::table('channels')->update(['theme' => 'electronics']);

        // Update all theme customizations so HomeController can find them
        // (it filters by channel->theme which is now 'electronics')
        DB::table('theme_customizations')->update(['theme_code' => 'electronics']);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::table('channels')->update(['theme' => 'default']);

        DB::table('theme_customizations')->update(['theme_code' => 'default']);
    }
};
