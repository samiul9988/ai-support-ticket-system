<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('category', 30);
            $table->decimal('confidence', 3, 2)->default(0.00);
            $table->text('reasoning')->nullable();
            $table->string('model', 50)->nullable();
            $table->boolean('is_auto_applied')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_classifications');
    }
};
