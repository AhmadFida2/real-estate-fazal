<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client' => $this->client,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'property_name' => $this->property_name,
            'inspection_type' => $this->inspection_type,
            'loan_number' => $this->loan_number,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'payment_info' => $this->payment_info,
        ];
    }
}
