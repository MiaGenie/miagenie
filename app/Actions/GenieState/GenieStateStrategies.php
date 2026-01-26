<?php

namespace App\Actions\GenieState;

use App\Abstracts\GenieData;
use App\Contracts\GenieStateContract;
use App\Enums\StrategyStatus;
use App\Models\Strategy;
use Illuminate\Support\Facades\Log;

class GenieStateStrategies
{
    /**
     * @param Strategy $strategy
     * @param string $state
     * @param ?bool $requiresReview
     */
    public function handle(Strategy $strategy, string $state, bool $requiresReview = false): void
    {
        try {
            switch ($state) {
                default:
                case 'run':
                    $status = StrategyStatus::RUNNING;
                    break;
                case 'end':
                    $status = $requiresReview ? StrategyStatus::PENDING_REVIEW : StrategyStatus::PENDING_APPROVAL;
                    break;
                case 'fail':
                    $status = StrategyStatus::ERROR;
                    break;
            }

            $strategy->update(['status' => $status]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
