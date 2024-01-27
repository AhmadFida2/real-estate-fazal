<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\InspectionResource;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

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
        $key = $data['temp_key'];
        unset($data['temp_key']);
        Cache::delete($key);
        $allKeys = Cache::get('temp_keys');
        if($allKeys)
        {
            $allKeys = array_diff($allKeys, [$key]);
        }
        Cache::forever('temp_keys', $allKeys);
        return $data;
    }


}
