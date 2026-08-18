<?php

namespace App\Console\Commands;

use App\Models\Monitoring\MonitoringScreenshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateMonitoringStorage extends Command
{
    protected $signature   = 'monitoring:migrate-storage';
    protected $description = 'One-off: move existing monitoring screenshots from the public disk to the local (private) disk.';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $local  = Storage::disk('local');
        $moved  = 0;
        $errors = 0;

        MonitoringScreenshot::chunkById(100, function ($screenshots) use ($public, $local, &$moved, &$errors) {
            foreach ($screenshots as $screenshot) {
                foreach (['file_path', 'thumbnail_path'] as $field) {
                    $path = $screenshot->$field;
                    if (!$path) {
                        continue;
                    }
                    if ($public->exists($path) && !$local->exists($path)) {
                        try {
                            $local->put($path, $public->get($path));
                            $public->delete($path);
                            $moved++;
                        } catch (\Throwable $e) {
                            $this->error("Failed to move {$path}: {$e->getMessage()}");
                            $errors++;
                        }
                    }
                }
            }
        });

        $this->info("Migration complete — files moved: {$moved}, errors: {$errors}.");
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
