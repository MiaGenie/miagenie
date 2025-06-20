<?php

namespace App\Jobs\Utils;

use App\Actions\Utils\DeleteProviderFile as DeleteFile;
use App\Actions\UploadFile;
use App\Enums\OpenAISyncStatus;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FileProviderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public int $tries = 3;

    /**
     * @var int
     */
    public int $timeout = 30;

    /**
     * @var string
     */
    private string $file;

    /**
     * @var string
     */
    private string $action;

    /**
     * @param string $file
     * @param string $action
     */
    public function __construct(string $file, string $action)
    {
        $this->file = $file;
        $this->action = $action;
    }

    /**
     * @param UploadFile $uploadFile
     * @param DeleteFile $deleteFile
     * @return void
     */
    public function handle(UploadFile $uploadFile, DeleteFile $deleteFile): void
    {
        if ($this->action === 'upload') {

            $response = $uploadFile($this->file);

            if (! $response) {

                $this->release(30);

                return;
            }
        } elseif ($this->action === 'delete') {

            $response = $deleteFile($this->file);

            if (! $response) {
                $this->release(30);

                return;
            }
        }
    }

    /**
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {
        $fileDb = File::find($this->file->id);
        // do failed stuff
        if ($this->action === 'upload') {
            $fileDb->status = OpenAISyncStatus::FAILED_UPLOAD;
        } elseif ($this->action === 'delete') {
            $fileDb->status = OpenAISyncStatus::FAILED_DELETE;
        }
        $fileDb->save();
    }
}
