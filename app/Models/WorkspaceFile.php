<?php

namespace App\Models;

use App\Enums\WorkspaceFileSource;
use App\Enums\WorkspaceFileType;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

class WorkspaceFile extends Model
{

    use HasUuid;
    use SoftDeletes;

    /**
     * @var string
     */
    public $table = 'genie_workspace_files';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'workspace_id',
        'name',
        'mime_type',
        'disk',
        'path',
        'size',
        'type',
        'source',
    ];

    protected $casts = [
        'type' => WorkspaceFileType::class,
        'source' => WorkspaceFileSource::class
    ];

    /**
     * @return HasOne
     */
    public function runResponseWorkspaceFile(): HasOne
    {
        return $this->HasOne(RunResponseWorkspaceFile::class, 'run_response_id');
    }

    /**
     * @return string
     */
    public function getFullPath(): string
    {
        if ($this->disk === 'external_media') {
            return $this->path;
        }

        return $this->filesystem()->path($this->path);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        if ($this->disk === 'external_media') {
            return $this->path;
        }

        return $this->filesystem()->url($this->path);
    }

    /**
     * @return string[]
     */
    public static function mimeTypes()
    {
        return  [
            'image/jpg',
            'image/jpeg',
            'image/gif',
            'image/png'
        ];
    }

    /**
     * @return int
     */
    public static function maxFileSize(): int
    {
        return 2048; // 2MB
    }

    /**
     * @return FilesystemAdapter
     */
    public function getAdapter(): FilesystemAdapter
    {
        return $this->filesystem($this->disk)->getAdapter();
    }


    /**
     * @param string $disk
     * @return Filesystem
     */
    public function filesystem(string $disk = ''): Filesystem
    {
        return Storage::disk($disk ?: $this->disk);
    }

}
