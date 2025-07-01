<?php

namespace App\Jobs\Utils;

use App\Actions\Utils\DeleteProviderAssistant as DeleteAssistant;
use App\Actions\UploadAssistant;
use App\Actions\UpdateAssistant;
use App\Enums\OpenAISyncStatus;
use App\Models\Assistant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AssistantProviderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public int $tries = 1;

    /**
     * @var int
     */
    public int $timeout = 30;

    /**
     * @var string
     */
    private string $assistant;

    /**
     * @var string
     */
    private string $action;

    /**
     * @param string $assistant
     * @param string $action
     */
    public function __construct(string $assistant, string $action)
    {
        $this->assistant = $assistant;
        $this->action = $action;
    }

    /**
     * @param UploadAssistant  $uploadAssistant
     * @param UpdateAssistant $updateAssistant
     * @param DeleteAssistant $deleteAssistant
     * @return void
     */
    public function handle(UploadAssistant $uploadAssistant, UpdateAssistant $updateAssistant, DeleteAssistant $deleteAssistant): void
    {
        if ($this->action === 'upload') {

            $response = $uploadAssistant($this->assistant);

            if (! $response) {

                $this->release(30);

                return;
            }
        } elseif ($this->action === 'update') {

            $response = $updateAssistant($this->assistant);

            if (! $response) {
                $this->release(30);

                return;
            }
        } elseif ($this->action === 'delete') {

            $response = $deleteAssistant($this->assistant);

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
        $assistantDb = Assistant::find($this->assistant);
        // do failed stuff
        if ($this->action === 'upload') {
            $assistantDb->status = OpenAISyncStatus::FAILED_UPLOAD;
        } elseif ($this->action === 'update') {
            $assistantDb->status = OpenAISyncStatus::FAILED_UPDATE;
        } elseif ($this->action === 'delete') {
            $assistantDb->status = OpenAISyncStatus::FAILED_DELETE;
        }
        $assistantDb->save();
    }
}
