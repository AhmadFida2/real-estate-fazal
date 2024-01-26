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
            'name' => $this->name,
            'address' => $this->address,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'overall_rating' => $this->overall_rating,
            'rating_scale' => $this->rating_scale,
            'inspection_date' => $this->inspection_date,
            'primary_type' => $this->primary_type,
            'secondary_type' => $this->secondary_type,
            'servicer_loan_info' => $this->servicer_loan_info,
            'contact_inspector_info' => $this->contact_inspector_info,
            'management_onsite_info' => $this->management_onsite_info,
            'comments' => $this->comments,
            'profile_occupancy_info' => $this->profile_occupancy_info,
            'capital_expenditures' => $this->capital_expenditures,
            'operation_maintenance_plans' => $this->operation_maintenance_plans,
            'neighborhood_site_data' => $this->neighborhood_site_data,
            'physical_condition' => $this->physical_condition,
            'images' => $this->formatImages($this->images),
            'rent_roll' => $this->rent_roll,
            'mgmt_interview' => $this->mgmt_interview,
            'multifamily' => $this->multifamily,
            'fannie_mae_assmt' => $this->fannie_mae_assmt,
            'fre_assmt' => $this->fre_assmt,
            'repairs_verification' => $this->repairs_verification
                ? [
                    'verification_list' => $this->formatRepairsVerificationPhotos($this->repairs_verification['verification_list']),
                    'property_info' => $this->repairs_verification['property_info'],
                    'contact_company' => $this->repairs_verification['contact_company'],
                    'contact_name' => $this->repairs_verification['contact_name'],
                    'contact_phone' => $this->repairs_verification['contact_phone'],
                    'contact_email' => $this->repairs_verification['contact_email'],
                    'inspection_company' => $this->repairs_verification['inspection_company'],
                    'inspector_name' => $this->repairs_verification['inspector_name'],
                    'inspector_company_phone' => $this->repairs_verification['inspector_company_phone'],
                    'inspector_id' => $this->repairs_verification['inspector_id'],
                    'servicer_name' => $this->repairs_verification['servicer_name'],
                    'loan_number' => $this->repairs_verification['loan_number'],
                    'primary_type' => $this->repairs_verification['primary_type'],
                    'expected_percentage_complete' => $this->repairs_verification['expected_percentage_complete'],
                    'overall_observed_percentage_complete' => $this->repairs_verification['overall_observed_percentage_complete'],
                    'general_summary_comments' => $this->repairs_verification['general_summary_comments'],
                ]
                : null,
            'senior_supplement' => $this->senior_supplement,
            'hospitals' => $this->hospitals
        ];
    }

    protected function formatRepairsVerificationPhotos($verificationList)
    {
        return collect($verificationList)->map(function ($item) {
            $item['photo'] = asset("storage/{$item['photo']}");
            return $item;
        })->all();
    }

    protected function formatImages($images)
    {
        return collect($images)->map(function ($image) {
            $image['photo_url'] = asset("storage/{$image['photo_url']}");
            return $image;
        })->all();
    }

}
