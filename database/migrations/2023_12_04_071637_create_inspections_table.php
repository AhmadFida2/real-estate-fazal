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
            $table->string('name');
            $table->string('address');
            $table->string('address_2')->nullable();
            $table->string('city');
            $table->foreignId('state_id');
            $table->string('zip');
            $table->string('country');
            $table->smallInteger('overall_rating');
            $table->string('rating_scale');
            $table->dateTime('inspection_date');
            $table->string('primary_type');
            $table->string('secondary_type');
            $table->json('servicer_loan_info');
            $table->json('contact_inspector_info');
            $table->json('management_onsite_info');
            $table->json('comments');
            $table->json('profile_occupancy_info');
            $table->json('capital_expenditures');
            $table->json('operation_maintenance_plans');
            $table->json('neighborhood_site_data');
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
            $table->foreignId('user_id');
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
