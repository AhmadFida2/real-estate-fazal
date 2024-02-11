<?php

namespace App\Filament\Resources\InspectionResource\Pages;

use App\Filament\Resources\InspectionResource;
use Filament\Actions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Markdown;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ListInspections extends ListRecords
{
    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    #[On('test-event')]
    public function test($record): void
    {
        $data = new \App\Http\Resources\InspectionResource($record);
        $data = $data->toJson();
        $d_file = Str::random(10) . '.txt';
        Storage::disk('public')->put($d_file, $data);
        $path = Storage::disk('local')->path('test.py') . " " . $d_file;
        exec("python3 $path", $output);
        $user = auth()->user();
        $fname = $output[0];
        Storage::disk('public')->delete($d_file);
        if ($fname == 'error') {
            Notification::make()
                ->title('File Generate Failed.')
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('File Generated')
                ->success()
                ->body(Markdown::inline('The requested Excel file is ready for download. **Once downloaded, file will be deleted from server.**'))
                ->actions([
                    Action::make('download')
                        ->button()
                        ->url('/excel-download/' . $fname)
                        ->extraAttributes(['x-on:click' => 'close'])
                ])
                ->sendToDatabase($user);
            event(new DatabaseNotificationsSent($user));

        }
    }
}
