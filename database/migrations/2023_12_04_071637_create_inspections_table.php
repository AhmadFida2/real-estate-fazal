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
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->foreignId('state_id')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->smallInteger('overall_rating')->nullable();
            $table->string('rating_scale')->nullable();
            $table->dateTime('inspection_date')->nullable();
            $table->string('primary_type')->nullable();
            $table->string('secondary_type')->nullable();
            $table->json('servicer_loan_info')->nullable();
            $table->json('contact_inspector_info')->nullable();
            $table->json('management_onsite_info')->nullable();
            $table->json('comments')->nullable();
            $table->json('profile_occupancy_info')->nullable();
            $table->json('capital_expenditures')->nullable();
            $table->json('operation_maintenance_plans')->nullable();
            $table->json('neighborhood_site_data')->nullable();
            $table->json('physical_condition')->nullable();
            $table->json('images')->nullable();
            $table->json('rent_roll')->nullable();
            $table->json('mgmt_interview')->nullable();
            $table->json('multifamily')->nullable();
            $table->json('fannie_mae_assmt')->nullable();
            $table->json('fre_assmt')->nullable();
            $table->json('repairs_verification')->nullable();
            $table->json('senior_supplement')->nullable();
            $table->json('hospitals')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
