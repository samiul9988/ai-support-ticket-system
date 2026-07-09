<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0)->after('is_published');
            $table->unsignedInteger('helpful_count')->default(0)->after('view_count');
            $table->unsignedInteger('not_helpful_count')->default(0)->after('helpful_count');
            $table->json('meta_keywords')->nullable()->after('not_helpful_count');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropColumn(['view_count', 'helpful_count', 'not_helpful_count', 'meta_keywords']);
        });
    }
};
