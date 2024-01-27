<?php

namespace App\Jobs;

use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CreateExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $record;
    /**
     * Create a new job instance.
     */
    public function __construct($record)
    {
        $this->record = $record;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Notification::make()
            ->title('Generating File')
            ->info()
            ->send();
        $data = new InspectionResource($this->record);
        $data = $data->toJson();
        Storage::disk('public')->put('temp_file.txt', $data);
        $path = Storage::disk('local')->path('public/test.py');
        exec("python3 {$path}", $output);
        $user = auth()->user();
        Notification::make()
            ->title('File Generated')
            ->success()
            ->body('The requested Excel file is ready for download')
            ->actions([
                Action::make('download')
                    ->button()
                    ->url('/excel-download/'. str($output))
            ])
            ->sendToDatabase($user);

    }
}
