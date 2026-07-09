<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('error_code', 30)->nullable()->index();
            $table->string('exception_class', 255);
            $table->integer('http_status')->default(500);
            $table->string('user_message', 255);
            $table->text('technical_message')->nullable();
            $table->longText('trace')->nullable();
            $table->json('context')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('exception_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
