<?php

namespace App\Support;

use App\Enums\WorkspaceFileSource;
use App\Enums\WorkspaceFileType;
use App\Models\WorkspaceFile;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Enums\GenieSyncStatus;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class FileUploader
{

    /**
     * @var UploadedFile|SymfonyUploadedFile
     */
    protected UploadedFile|SymfonyUploadedFile $file;

    /**
     * @var string
     */
    protected string $disk;

    /**
     * @var string
     */
    protected string $path = '';

    /**
     * @var array|null
     */
    protected ?array $data = null;

    /**
     * @var array
     */
    protected array $conversions;

    /**
     * @param UploadedFile|SymfonyUploadedFile $file
     */
    public function __construct(UploadedFile|SymfonyUploadedFile $file)
    {
        $this->setFile($file);
        $this->disk('local');
    }

    /**
     * @param UploadedFile $file
     * @return static
     */
    public static function fromFile(UploadedFile $file): static
    {
        return new static($file);
    }

    /**
     * @param SymfonyUploadedFile $file
     * @return static
     */
    public static function createFromBase(SymfonyUploadedFile $file): static
    {
        $newFile = UploadedFile::createFromBase($file);
        return new static($newFile);
    }

    /**
     * @param UploadedFile|SymfonyUploadedFile $file
     * @return $this
     */
    public function setFile(UploadedFile|SymfonyUploadedFile $file): static
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function disk(string $name): static
    {
        $this->disk = $name;

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function upload(): array
    {
        $path = $this->filesystem()->put($this->path, $this->file);

        if (!$path) {
            throw new \Exception("The file was not uploaded. Check your $this->disk driver configuration.");
        }

        return [
            'name' => $this->file->getClientOriginalName(),
            'mime_type' => $this->file->getMimeType(),
            'disk' => $this->disk,
            'path' => $path,
            'size' => $this->file->getSize(),
        ];
    }

    /**
     * @return File
     * @throws \Exception
     */
    public function uploadAndInsertFile(): File
    {
        return File::create(
            array_merge(
                $this->upload(),
                ['status' => GenieSyncStatus::CREATING]
            )
        );
    }

    /**
     * @param WorkspaceFileType $type
     * @param WorkspaceFileSource $source
     * @return WorkspaceFile
     * @throws \Exception
     */
    public function uploadAndInsertWorkspaceFile(WorkspaceFileType $type, WorkspaceFileSource $source): WorkspaceFile
    {
        return WorkspaceFile::create(
            array_merge(
                $this->upload(),
                [
                    'workspace_id' => WorkspaceManager::current()->id,
                    'type' => $type,
                    'source' => $source
                ]
            )
        );
    }

    /**
     * @return Filesystem
     */
    protected function filesystem(): Filesystem
    {
        return Storage::disk($this->disk);
    }
}
