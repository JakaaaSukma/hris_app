<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lead_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('employees')->cascadeOnDelete();
            $table->string('client_name');
            $table->string('project_title');
            $table->decimal('deal_value', 15, 2);
            $table->enum('stage', ['prospect', 'proposal', 'negotiation', 'won', 'lost'])->default('prospect');
            $table->date('target_close_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_projects');
    }
};
