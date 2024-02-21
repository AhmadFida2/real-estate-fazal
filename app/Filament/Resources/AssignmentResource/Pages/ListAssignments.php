<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class ListAssignments extends ListRecords
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


    #[On('gen-invoice')]
    public function invoice($id)
    {
        $file_name = 'invoice_' . $id . ".pdf";
        if (file_exists(public_path($file_name))) {
            return response()->download(public_path($file_name));
        }
        $path = Storage::disk('local')->path('invoice.py') . " " . $id;
        exec("python3 $path", $output);
        if ($output)
        {
            return response()->download(public_path($file_name))->deleteFileAfterSend();
        }
        else
        {
            Notification::make()
                ->title('Invoice Generation Failed!')
                ->danger()
                ->send();
        }
    }
}
