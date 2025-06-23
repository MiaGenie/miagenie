<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

class File extends Model
{

    use HasUuid;
    use SoftDeletes;

    /**
     * @var string
     */
    public $table = 'genie_files';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'name',
        'mime_type',
        'disk',
        'path',
        'status',
        'size',
        'file_provider_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'id' => 'string',
    ];


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
        return [
            'application/pdf' => 'PDF',
            'application/json' => 'JSON',
            'text/plain' => 'Text File (txt)',
            'application/msword' => 'Word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word (OpenXML)',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint (OpenXML)',
        ];
    }

    /**
     * @return int
     */
    public static function maxFileSize(): int
    {
        return 102400; // 100MB
    }

    /**
     * @return FilesystemAdapter
     */
    public function getAdapter(): FilesystemAdapter
    {
        return $this->filesystem($this->disk)->getAdapter();
    }

    /**
     * @return bool
     */
    public function isLocalAdapter(): bool
    {
        $adapter = $this->getAdapter();

        return $adapter instanceof LocalFilesystemAdapter;
    }

    /**
     * @return void
     */
    public function deleteFiles(): void
    {
        $this->filesystem()->delete($this->path);
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
