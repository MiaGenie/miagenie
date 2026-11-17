<?php

namespace Tests\Unit;

use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use App\Http\Resources\Admin\AiRunStepResource;
use App\Models\AiRunStep;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The admin run-step page prints the step's error as it receives it, so the resource hands over
 * the code's name rather than the stored number.
 */
class AiRunStepResourceTest extends TestCase
{
    public function test_a_failed_step_shows_its_error_by_name(): void
    {
        $array = (new AiRunStepResource($this->step(RunResponseError::INSUFFICIENT_CREDITS)))->toArray(new Request);

        $this->assertSame('insufficient_credits', $array['error']);
    }

    public function test_a_step_without_an_error_shows_none(): void
    {
        $array = (new AiRunStepResource($this->step(null)))->toArray(new Request);

        $this->assertNull($array['error']);
    }

    protected function step(?RunResponseError $error): AiRunStep
    {
        return (new AiRunStep)->forceFill([
            'uuid' => 'step-uuid',
            'status' => RunStatus::ERROR,
            'error' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
