<?php

namespace App\Http\Requests\Workspace\Strategy;

use App\Concerns\IngestVersionsContent;
use App\Enums\GenieSyncAction;
use App\Enums\GenieSyncStatus;
use App\Enums\RunStatus;
use App\Jobs\RunJob;
use App\Models\RunResponseReview;
use App\Models\Strategy;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class ReviewUpdateStrategy extends FormRequest
{
    use IngestVersionsContent;

    /**
     * @var array
     */
    private array $validationRules;

    /**
     * @var Collection
     */
    private Collection $fieldList;

    /**
     * @return array
     */
    public function rules(): array
    {
        $this->validationRules = [];
        return $this->validationRules;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $strategy = Strategy::firstOrFailByUuid($this->route('strategy'));
        $field = $this->input('field');

        $original = array_map('trim', $strategy->content[$field]);
        $reviewed = array_map('trim', $this->input($field));
        $diff = array_diff($original, $reviewed);

        $run = $strategy->run;
        $response = $run->runResponses->last();

        if (!empty($diff)) {
            $content = $strategy->content;
            $content[$field] = $this->input($field);
            $strategy->content = $content;
            $strategy->save();

            RunResponseReview::create([
                'run_response_id' => $response->id,
                'original' => $original,
                'reviewed' => $reviewed
            ]);
        }

        $response->update(['status' => RunStatus::COMPLETE]);
        $run->update(['status' => RunStatus::RUNNING]);

        RunJob::dispatch($run, GenieSyncAction::UPDATE);
    }

    /**
     * @return void
     * @throws \Exception
     */
/*    protected function prepareForValidation(): void
    {
        $this->fieldList = Version::findByUuid($this->input('version'))
            ->with(['strategies' => ['options']])
            ->firstOrFail()
            ->strategies;

        $this->validationRules = $this->getValidationRules()->toArray();
    }*/
}
