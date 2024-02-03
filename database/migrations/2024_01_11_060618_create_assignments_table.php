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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('client');
            $table->tinyInteger('status');
            $table->date('start_date');
            $table->date('due_date');
            $table->string('property_name');
            $table->tinyInteger('inspection_type');
            $table->string('loan_number');
            $table->string('city');
            $table->string('state');
            $table->integer('zip');
            $table->boolean('is_completed')->default(false);
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
