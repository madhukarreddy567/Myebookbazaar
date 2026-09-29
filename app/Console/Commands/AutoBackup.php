<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Support\Audit;
use Illuminate\Console\Command;

/**
 * Phase 7: daily automatic backup (retention handled by BackupService::prune).
 */
class AutoBackup extends Command
{
    protected $signature = 'backups:auto';
    protected $description = 'Create the daily automatic backup.';

    public function handle(BackupService $backups): int
    {
        try {
            $backup = $backups->create('auto');
            Audit::record('backup.created', 'backup', $backup->id, [], [
                'type' => 'auto',
                'filename' => $backup->filename,
                'size' => $backup->sizeLabel(),
            ]);
            $this->line('Backup created: '.$backup->filename.' ('.$backup->sizeLabel().')');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Automatic backup failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
