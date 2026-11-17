<?php

namespace App\Http\Requests\Workspace\Strategy;

use App\Enums\StrategyStatus;
use App\Models\Strategy;
use Illuminate\Foundation\Http\FormRequest;

class ApproveStrategy extends FormRequest
{
    /**
     * Only a strategy its run has finished can be approved: approving one still being generated —
     * or the empty one an admin's regeneration leaves waiting — would hand ideas a strategy with
     * nothing in it.
     */
    public function handle(): bool
    {
        $strategy = Strategy::findByUuid($this->input('id'));

        if ($strategy?->status !== StrategyStatus::PENDING_APPROVAL) {
            return false;
        }

        return $strategy->update([
            'status' => StrategyStatus::APPROVED,
        ]);
    }
}
