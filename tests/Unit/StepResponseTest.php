<?php

namespace Tests\Unit;

use App\Ai\StepResponse;
use App\Enums\RunResponseError;
use App\Enums\RunResponseStatus;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;
use Tests\TestCase;

class StepResponseTest extends TestCase
{
    public function test_known_statuses_map_to_their_case(): void
    {
        $this->assertSame(RunResponseStatus::COMPLETED, StepResponse::status('completed'));
        $this->assertSame(RunResponseStatus::IN_PROGRESS, StepResponse::status('in_progress'));
    }

    public function test_an_unknown_status_degrades_instead_of_throwing(): void
    {
        // The previous mapping used constant(), which raised an uncatchable \Error that the
        // surrounding catch (\Exception) could not handle, crashing the job.
        $this->assertSame(RunResponseStatus::FAILED, StepResponse::status('something_new'));
    }

    public function test_a_blank_status_is_null(): void
    {
        $this->assertNull(StepResponse::status(null));
        $this->assertNull(StepResponse::status(''));
    }

    public function test_an_unknown_error_code_degrades_to_server_error(): void
    {
        $this->assertSame(RunResponseError::RATE_LIMIT_EXCEEDED, StepResponse::error('rate_limit_exceeded'));
        $this->assertSame(RunResponseError::SERVER_ERROR, StepResponse::error('anthropic_overloaded'));
        $this->assertNull(StepResponse::error(null));
    }

    public function test_a_rate_limit_exception_maps_to_the_rate_limit_case(): void
    {
        $response = StepResponse::fromThrowable(new RateLimitedException('429'));

        $this->assertSame('failed', $response['status']);
        $this->assertSame('rate_limit_exceeded', $response['error']['code']);
        $this->assertNull($response['id']);
        $this->assertNull($response['structured']);
    }

    public function test_an_unrecognised_exception_maps_to_server_error(): void
    {
        $response = StepResponse::fromThrowable(new RuntimeException('boom'));

        $this->assertSame('server_error', $response['error']['code']);
        $this->assertSame('boom', $response['error']['message']);
    }
}
