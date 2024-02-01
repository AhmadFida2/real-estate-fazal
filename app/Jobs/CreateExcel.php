<?php

namespace App\Jobs;

use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Markdown;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $record_id;
    /**
     * Create a new job instance.
     */
    public function __construct($record)
    {
        $this->record_id = $record;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = new InspectionResource(Inspection::find($this->record_id));
        $data = $data->toJson();
        $d_file = Str::random(10) . '.txt';
        Storage::disk('public')->put($d_file, $data);
        $path = Storage::disk('local')->path('test.py') . " " . $d_file;
        exec("python3 $path", $output);
        $user = auth()->user();
        dd($output);
        $fname = $output[0];
        if($fname == 'error')
        {
            Notification::make()
                ->title('File Generate Failed.')
                ->danger()
                ->broadcast($user);
        }
        else
        {
            Notification::make()
                ->title('File Generated')
                ->success()
                ->body(Markdown::inline('The requested Excel file is ready for download. **Once downloaded, file will be deleted from server.**'))
                ->actions([
                    Action::make('download')
                        ->button()
                        ->url('/excel-download/' . $fname)
                ])
                ->sendToDatabase($user);
            event(new DatabaseNotificationsSent($user));

        }

    }
}
