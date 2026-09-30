<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupService;
use App\Support\Audit;
use Illuminate\Http\Request;

/**
 * Phase 7: backup management (Super Admin only).
 */
class BackupController extends Controller
{
    public function index()
    {
        $backups = Backup::orderByDesc('id')->paginate(20);

        return view('admin.backups.index', compact('backups'));
    }

    public function store(BackupService $backups)
    {
        try {
            $backup = $backups->create('manual');
        } catch (\Throwable $e) {
            return redirect()->route('reddy.backups.index')
                ->with('error', 'Backup failed: '.$e->getMessage());
        }

        Audit::record('backup.created', 'backup', $backup->id, [], [
            'type' => 'manual',
            'filename' => $backup->filename,
            'size' => $backup->sizeLabel(),
        ]);

        return redirect()->route('reddy.backups.index')
            ->with('status', 'Backup created: '.$backup->filename.' ('.$backup->sizeLabel().').');
    }

    public function download(Backup $backup, BackupService $backups)
    {
        try {
            $path = $backups->downloadPath($backup);
        } catch (\Throwable $e) {
            return redirect()->route('reddy.backups.index')->with('error', $e->getMessage());
        }

        Audit::record('backup.downloaded', 'backup', $backup->id);

        return response()->download($path, $backup->filename);
    }

    public function destroy(Backup $backup, BackupService $backups)
    {
        $filename = $backup->filename;
        $backups->delete($backup);

        Audit::record('backup.deleted', 'backup', null, [], ['filename' => $filename]);

        return redirect()->route('reddy.backups.index')
            ->with('status', 'Backup deleted.');
    }

    public function restore(Request $request, Backup $backup, BackupService $backups)
    {
        $request->validate([
            'confirm_restore' => ['accepted'],
        ], [
            'confirm_restore.accepted' => 'Tick the confirmation box to restore this backup.',
        ]);

        try {
            $backups->restore($backup);
        } catch (\Throwable $e) {
            return redirect()->route('reddy.backups.index')
                ->with('error', 'Restore failed: '.$e->getMessage());
        }

        Audit::record('backup.restored', 'backup', $backup->id, [], ['filename' => $backup->filename]);

        return redirect()->route('reddy.backups.index')
            ->with('status', 'Backup restored. The site is running the restored code and database.');
    }
}
