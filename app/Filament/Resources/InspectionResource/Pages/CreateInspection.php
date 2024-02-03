<?php

namespace App\Filament\Resources\InspectionResource\Pages;

use App\Filament\Resources\InspectionResource;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

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

    protected function afterFill(): void
    {
        $data = [];
        if(Session::has('assignment_data'))
        {
            $model = Session::get('assignment_data');
            $data['form_steps'] = [1];
            $data['name'] = $model->name;
            $data['city'] = $model->city;
            $data['state'] = $model->state;
            $data['zip'] = $model->zip;
            $data['servicer_loan_info']['loan_number'] = $model->loan_number;
            $this->form->fill($data);
        }

    }


}
