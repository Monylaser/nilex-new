<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Placeholder for migrating category icons to Spatie Media Library.
     *
     * Spatie uses the existing polymorphic `media` table; no schema change here.
     * The legacy `categories.icon` column is retained until backfill is verified.
     */
    public function up(): void
    {
        // No schema changes required.
    }

    public function down(): void
    {
        // No schema changes required.
    }
};
