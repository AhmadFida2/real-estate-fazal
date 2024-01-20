<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\InspectionResource;

use Filament\Resources\Pages\CreateRecord;

class CreateInspection extends CreateRecord
{
    // use CreateRecord\Concerns\HasWizard;


    protected static string $resource = InspectionResource::class;

    protected static bool $canCreateAnother = false;


    protected function hasSkippableSteps(): bool
    {
        return true;
    }


    public function getSteps(): array
    {
        return [
            InspectionResource::reportSelectStep(),
            InspectionResource::reportBasicStep(),
            InspectionResource::reportPhysicalStep(),
            InspectionResource::reportPhotoStep(),
            InspectionResource::reportRentStep(),
            InspectionResource::reportMgmtInterviewStep(),
            InspectionResource::reportMultifamilyStep(),
            InspectionResource::reportFannieMaeStep(),
            InspectionResource::reportFREStep(),
            InspectionResource::reportRepairStep(),
            InspectionResource::reportSeniorStep(),
            InspectionResource::reportHospitalStep(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

}
