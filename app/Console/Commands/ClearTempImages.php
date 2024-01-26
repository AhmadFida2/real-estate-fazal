<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ClearTempImages extends Command
{
    protected $signature = 'clear:temp-urls';
    protected $description = 'Clear temporary URLs older than 1 hour from storage';

    public function handle()
    {
        $allTimestampKeys = Cache::get('temp_keys') ?? [];

        foreach ($allTimestampKeys as $timestampKey) {
            $timestamp = $timestampKey;
            // Check if the timestamp key is older than 1 hour (60 minutes)
            // now()->subMinutes(15)->timestamp > ($timestamp)
            if (now()->subMinutes(15)->timestamp > ($timestamp)) {
                $urls = Cache::get($timestampKey, []);

                foreach ($urls as $url) {
                    // Assuming URLs are stored as file paths in storage
                    $filePath = 'public/' . $url;
                    Storage::delete($filePath);
                }

                // Optionally, you can remove the cache keys after deleting files
                Cache::forget($timestampKey);

                // Remove the timestamp key from 'all_timestamp_keys'
                $allTimestampKeys = array_diff($allTimestampKeys, [$timestampKey]);
                Cache::forever('temp_keys', $allTimestampKeys);
            }
        }

        $this->info('Temporary URLs older than 15 mins cleared from storage.');
    }
}
