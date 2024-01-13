<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Name' => $this->name,
            'Address' => $this->address,
            'Address 2' => $this->address_2,
            'City' => $this->city,
            'State ID' => $this->state_id,
            'Zip' => $this->zip,
            'Country' => $this->country,
            'Overall Rating' => $this->overall_rating,
            'Rating Scale' => $this->rating_scale,
            'Inspection Date' => $this->inspection_date,
            'Primary Type' => $this->primary_type,
            'Secondary Type' => $this->secondary_type,
            'Servicer Loan Info' => $this->servicer_loan_info,
            'Contact Inspector Info' => $this->contact_inspector_info,
            'Management Onsite Info' => $this->management_onsite_info,
            'Comments' => $this->comments,
            'Profile Occupancy Info' => $this->profile_occupancy_info,
            'Capital Expenditures' => $this->capital_expenditures,
            'Operation Maintenance Plans' => $this->operation_maintenance_plans,
            'Neighborhood Site Data' => $this->neighborhood_site_data,
            'Physical Condition' => $this->physical_condition,
            'Images' => $this->images,
            'rent_roll' => $this->rent_roll,
            'mgmt_interview' => $this->mgmt_interview,
            'multifamily' => $this->multifamily,
            'fannie_mae_assmt' => $this->fannie_mae_assmt,
            'fre_assmt' => $this->fre_assmt,
            'repairs_verification' => $this->repairs_verification,
            'senior_supplement' => $this->senior_supplement,
            'hospitals' => $this->hospitals
        ];
    }
}
