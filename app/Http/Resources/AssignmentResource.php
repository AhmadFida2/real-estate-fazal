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
            'status' => match ($this->status) {
                0 => 'Un-Scheduled',
                1 => 'Scheduled',
            },
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'property_name' => $this->property_name,
            'inspection_type' => match ($this->inspection_type) {
                0 => 'Basic',
                1 => 'Fannie Mae',
                2 => 'Repairs Verification',
                3 => 'Freddie Mac'
            },
            'loan_number' => $this->loan_number,
            'investor_number' => $this->investor_number,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'invoice_info' => $this->payment_info,
            'payments' => $this->payments()
        ];
    }
}
