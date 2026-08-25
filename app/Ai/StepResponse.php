<?php

namespace App\Ai;

use App\Enums\RunResponseError;
use App\Enums\RunResponseStatus;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

/**
 * Normalises an SDK response into the provider-agnostic array the run pipeline stores.
 *
 * The previous shape was OpenAI's Responses payload read positionally
 * (`output[0].content[0].text`), which broke on reasoning models because item 0 is a reasoning
 * block with no content. The SDK gives text and structured data directly, so nothing here
 * depends on item ordering.
 *
 * @phpstan-type NormalisedResponse array{
 *     id: ?string,
 *     conversation_id: ?string,
 *     status: string,
 *     text: string,
 *     structured: ?array<string, mixed>,
 *     error: ?array{code: string, message: string},
 *     usage: array<string, mixed>,
 *     meta: array<string, mixed>
 * }
 */
class StepResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function fromAgentResponse(AgentResponse $response): array
    {
        return [
            'id' => $response->invocationId,
            'conversation_id' => $response->conversationId,
            'status' => RunResponseStatus::COMPLETED->title(),
            'text' => (string) $response->text,
            'structured' => $response instanceof StructuredAgentResponse
                ? $response->toArray()
                : null,
            'error' => null,
            'usage' => $response->usage?->toArray() ?? [],
            'meta' => $response->meta?->toArray() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromThrowable(Throwable $exception): array
    {
        return [
            'id' => null,
            'status' => RunResponseStatus::FAILED->title(),
            'text' => '',
            'structured' => null,
            'error' => [
                'code' => self::errorCode($exception)->title(),
                'message' => $exception->getMessage(),
            ],
            'usage' => [],
            'meta' => [],
        ];
    }

    /**
     * Map an exception onto the stored error enum.
     *
     * This replaces `RunResponseError::fromName($response['error']['code'])`, which used
     * `constant()` and therefore threw an uncatchable `\Error` on any code outside its 18
     * OpenAI-specific cases. Anything unrecognised now degrades to SERVER_ERROR.
     */
    public static function errorCode(Throwable $exception): RunResponseError
    {
        return match (true) {
            $exception instanceof RateLimitedException => RunResponseError::RATE_LIMIT_EXCEEDED,
            $exception instanceof InsufficientCreditsException,
            $exception instanceof ProviderOverloadedException,
            $exception instanceof ProviderConnectionException => RunResponseError::SERVER_ERROR,
            default => RunResponseError::SERVER_ERROR,
        };
    }

    /**
     * Resolve a status string to the enum without throwing on unknown values.
     */
    public static function status(?string $status): ?RunResponseStatus
    {
        if (blank($status)) {
            return null;
        }

        foreach (RunResponseStatus::cases() as $case) {
            if ($case->title() === $status) {
                return $case;
            }
        }

        return RunResponseStatus::FAILED;
    }

    /**
     * Resolve an error code string to the enum without throwing on unknown values.
     */
    public static function error(?string $code): ?RunResponseError
    {
        if (blank($code)) {
            return null;
        }

        foreach (RunResponseError::cases() as $case) {
            if ($case->title() === $code) {
                return $case;
            }
        }

        return RunResponseError::SERVER_ERROR;
    }
}
