<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'contact_inspector_info' => 'json',
        'servicer_loan_info' => 'json',
        'management_onsite_info' => 'json',
        'comments' => 'json',
        'profile_occupancy_info' => 'json',
        'capital_expenditures' => 'json',
        'operation_maintenance_plans' => 'json',
        'neighborhood_site_data' => 'json',
        'physical_condition' => 'json',
        'images' => 'json',
        'rent_roll' => 'json',
        'form_steps' => 'json',
        'mgmt_interview' => 'json',
        'multifamily' => 'json',
        'fannie_mae_assmt' => 'json',
        'fre_assmt' => 'json',
        'repairs_verification' => 'json',
        'senior_supplement' => 'json',
        'hospitals' => 'json',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
