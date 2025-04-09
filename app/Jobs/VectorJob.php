<?php

namespace App\Jobs;

use App\Actions\DeleteVector;
use App\Actions\UploadVector;
use App\Enums\FileStatus;
use App\Models\Vector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class VectorJob implements ShouldQueue
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
     * @var Vector
     */
    private Vector $vector;

    /**
     * @var string
     */
    private string $action;

    /**
     * @param Vector $vector
     * @param string $action
     */
    public function __construct(Vector $vector, string $action)
    {
        $this->vector = $vector;
        $this->action = $action;
    }

    /**
     * @param UploadVector $uploadVector
     * @param DeleteVector $deleteVector
     * @return void
     */
    public function handle(UploadVector $uploadVector, DeleteVector $deleteVector): void
    {
        if ($this->action === 'upload') {

            $response = $uploadVector($this->vector);

            if (! $response) {

                $this->release(30);

                return;
            }
        } elseif ($this->action === 'delete') {

            $vectorDb = Vector::find($this->vector->id);
            $vectorDb->status = FileStatus::TO_DELETE;
            $vectorDb->save();

            $response = $deleteVector($this->vector);

            if (! $response) {
                $this->release(30);

                return;
            }

            $this->vector->delete();
        }
    }

    /**
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {
        $vectorDb = Vector::find($this->vector->id);
        // do failed stuff
        if ($this->action === 'upload') {
            $vectorDb->status = FileStatus::FAILED;
        } elseif ($this->action === 'delete') {
            $vectorDb->status = FileStatus::FAILED_DELETION;
        }
        $vectorDb->save();
    }
}
