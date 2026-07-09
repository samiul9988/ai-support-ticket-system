<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->string('http_status', 5)->nullable()->after('total_tokens');
            $table->string('request_endpoint', 255)->nullable()->after('http_status');
            $table->string('operation', 50)->nullable()->after('request_endpoint');
            $table->integer('request_payload_size')->default(0)->after('operation');
            $table->text('response_body')->nullable()->after('request_payload_size');
            $table->integer('retry_count')->default(0)->after('response_body');
            $table->string('error_type', 30)->nullable()->after('error_message');
            $table->string('log_level', 10)->default('info')->after('error_type');

            $table->index('operation');
            $table->index('http_status');
            $table->index('log_level');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropColumn([
                'http_status', 'request_endpoint', 'operation',
                'request_payload_size', 'response_body', 'retry_count',
                'error_type', 'log_level',
            ]);
        });
    }
};
