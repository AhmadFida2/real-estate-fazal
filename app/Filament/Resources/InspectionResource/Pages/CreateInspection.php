<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\InspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInspection extends CreateRecord
{
    protected static string $resource = InspectionResource::class;

    protected static bool $canCreateAnother = false;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
