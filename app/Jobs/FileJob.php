<?php

namespace App\Jobs;

use App\Actions\DeleteFile;
use App\Actions\UploadFile;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var File
     */
    private File $file;

    /**
     * @var string
     */
    private string $action;

    /**
     * @param File $file
     * @param string $action
     */
    public function __construct(File $file, string $action)
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

            $this->file->delete();
        }
    }
}
