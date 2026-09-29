<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Phase 7: a backup record. The actual archive lives in
 * storage/app/backups/{filename}. Contains database.sql plus the project
 * files (excluding vendor, node_modules and the backups dir itself).
 */
class Backup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'filename',
        'type',
        'size_bytes',
        'version',
        'commit_sha',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'auto' => 'Automatic',
            'pre-update' => 'Pre-update',
            default => 'Manual',
        };
    }

    public function sizeLabel(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1073741824, 2).' GB';
    }
}
