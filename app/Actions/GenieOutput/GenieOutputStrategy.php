<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Concerns\CleanAsterisks;
use App\Concerns\PersistsStepResponse;
use App\Contracts\GenieOutputContract;
use App\Enums\RuleSubType;
use App\Enums\RunStatus;
use App\Models\RunResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenieOutputStrategy extends GenieOutput implements GenieOutputContract
{
    use CleanAsterisks;
    use PersistsStepResponse;

    public function handle(GenieData $data): void
    {
        /** @var RunResponse $model */
        $model = $data->getModel();
        $response = $data->getResponse();

        try {
            parent::handle($data);

            $this->persistResponse($model, $response);

            $strategy = $model->run->strategy;
            $content = $strategy->content ?? [];
            $outputs = $model->step->output ?? [];

            if ($outputs === []) {
                throw new \RuntimeException("Step [{$model->step->id}] has no output field.");
            }

            $strategy->content = array_merge(
                $content,
                $this->resolveContent($model, $response, $outputs)
            );

            $strategy->update();
        } catch (Throwable $exception) {
            // A parse failure used to be swallowed here, leaving the field unwritten while the
            // run still reported success. The response is now marked errored so the run stops.
            Log::error('Genie strategy output failed', [
                'run_response_id' => $model->id,
                'step_id' => $model->step_id,
                'exception' => $exception->getMessage(),
            ]);

            $data->setError(true);
            $model->update(['status' => RunStatus::ERROR]);
        }
    }

    /**
     * Map a step's response onto the strategy content keys it writes.
     *
     * @param  array<string, mixed>  $response
     * @param  array<int, string>  $outputs
     * @return array<string, mixed>
     */
    protected function resolveContent(RunResponse $model, array $response, array $outputs): array
    {
        $step = $model->step;
        $firstOutput = $outputs[0];

        if ($step->response_format !== 'json_schema') {
            return [$firstOutput => $this->cleanAsterisks((string) $response['text'])];
        }

        $structured = $this->structuredOutput($response);

        if ($structured === null) {
            throw new \RuntimeException("Step [{$step->id}] returned no structured output.");
        }

        // A multi-output step writes every key it declared; every other sub-type writes one.
        if ($step->rule_sub_type === RuleSubType::BRIEFINGS_MULTIPLE) {
            $content = [];

            foreach ($outputs as $output) {
                $content[$output] = $structured[$output] ?? null;
            }

            return $content;
        }

        $value = $structured[$firstOutput] ?? null;

        $outputField = $model->run->rule->version->fields
            ->where('code_name', $firstOutput)
            ->first();

        // Checkbox fields are returned as a map of option => bool; store the selected keys.
        if ($outputField?->field_type->name === 'CHECKBOX' && is_array($value)) {
            $value = array_keys($value, true);
        }

        return [$firstOutput => $value];
    }
}
