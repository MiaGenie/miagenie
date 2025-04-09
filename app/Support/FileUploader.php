<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Enums\OpenAISyncStatus;

class FileUploader
{

    /**
     * @var UploadedFile
     */
    protected UploadedFile $file;

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
     * @param UploadedFile $file
     */
    public function __construct(UploadedFile $file)
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
     * @param UploadedFile $file
     * @return $this
     */
    public function setFile(UploadedFile $file): static
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
            'status' => OpenAISyncStatus::CREATED,
            'size' => $this->file->getSize(),
        ];
    }

    /**
     * @return File
     * @throws \Exception
     */
    public function uploadAndInsert(): File
    {
        return File::create(
            Arr::only($this->upload(), ['name', 'mime_type', 'disk', 'path', 'status', 'size'])
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
