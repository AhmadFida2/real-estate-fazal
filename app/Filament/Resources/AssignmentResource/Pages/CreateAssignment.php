<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function afterCreate($record): void
    {
        $user = $record->user();
        Notification::make()
            ->title('New Assignment!')
            ->info()
            ->body('You have a new assignment.')
            ->actions([
                Action::make('view')
                ->url(AssignmentResource::getUrl())
            ])->sendToDatabase($user);
    }

}
