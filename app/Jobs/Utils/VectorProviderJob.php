<?php

namespace App\Jobs\Utils;

use App\Actions\Utils\DeleteProviderVector as DeleteVector;
use App\Actions\UploadVector;
use App\Enums\OpenAISyncStatus;
use App\Models\Vector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class VectorProviderJob implements ShouldQueue
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
    private string $vector;

    /**
     * @var string
     */
    private string $action;

    /**
     * @param string $vector
     * @param string $action
     */
    public function __construct(string $vector, string $action)
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

            $response = $deleteVector($this->vector);

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
        $vectorDb = Vector::find($this->vector->id);
        // do failed stuff
        if ($this->action === 'upload') {
            $vectorDb->status = OpenAISyncStatus::FAILED_UPLOAD;
        } elseif ($this->action === 'delete') {
            $vectorDb->status = OpenAISyncStatus::FAILED_DELETE;
        }
        $vectorDb->save();
    }
}
