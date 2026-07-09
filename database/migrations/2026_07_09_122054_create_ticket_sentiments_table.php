<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sentiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('ticket_reply_id')->nullable()->constrained('ticket_replies')->nullOnDelete();
            $table->string('sentiment', 20);
            $table->decimal('confidence', 3, 2)->default(0.00);
            $table->text('analysis_text')->nullable();
            $table->string('model', 50)->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index('sentiment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sentiments');
    }
};
