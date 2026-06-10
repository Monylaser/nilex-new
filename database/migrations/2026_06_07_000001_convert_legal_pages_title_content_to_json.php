<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1 — Add temporary JSON columns next to the existing string columns
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->json('title_json')->nullable()->after('id');
            $table->json('content_json')->nullable()->after('title_json');
        });

        // Step 2 — Migrate existing string data → {"ar": "<value>"}
        //           If the value is already valid JSON (array), keep it as-is.
        DB::table('legal_pages')->orderBy('id')->each(function (object $row) {
            $titleArr   = @json_decode($row->title, true);
            $contentArr = @json_decode($row->content, true);

            DB::table('legal_pages')->where('id', $row->id)->update([
                'title_json'   => json_encode(
                    is_array($titleArr) ? $titleArr : ['ar' => $row->title]
                ),
                'content_json' => json_encode(
                    is_array($contentArr) ? $contentArr : ['ar' => $row->content]
                ),
            ]);
        });

        // Step 3 — Drop the old string/longText columns
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->dropColumn(['title', 'content']);
        });

        // Step 4 — Rename JSON columns to their original names
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->renameColumn('title_json', 'title');
            $table->renameColumn('content_json', 'content');
        });
    }

    public function down(): void
    {
        // Step 1 — Add temporary string columns
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->string('title_str')->nullable()->after('id');
            $table->longText('content_str')->nullable()->after('title_str');
        });

        // Step 2 — Extract Arabic value from JSON
        DB::table('legal_pages')->orderBy('id')->each(function (object $row) {
            $titleArr   = json_decode($row->title, true);
            $contentArr = json_decode($row->content, true);

            DB::table('legal_pages')->where('id', $row->id)->update([
                'title_str'   => $titleArr['ar'] ?? ($titleArr['en'] ?? ''),
                'content_str' => $contentArr['ar'] ?? ($contentArr['en'] ?? ''),
            ]);
        });

        // Step 3 — Drop JSON columns
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->dropColumn(['title', 'content']);
        });

        // Step 4 — Rename back
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->renameColumn('title_str', 'title');
            $table->renameColumn('content_str', 'content');
        });
    }
};
