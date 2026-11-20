<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bookmarks parsed before the content_failed status existed have no
     * content and make the reader poll forever.
     */
    public function up(): void
    {
        DB::table('bookmarks')
            ->where('status', 'parsed')
            ->whereNull('content_html')
            ->update(['status' => 'content_failed']);
    }

    public function down(): void
    {
        DB::table('bookmarks')
            ->where('status', 'content_failed')
            ->update(['status' => 'parsed']);
    }
};
