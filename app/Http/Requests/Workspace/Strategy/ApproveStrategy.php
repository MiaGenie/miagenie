<?php

namespace App\Http\Requests\Workspace\Strategy;

use App\Enums\StrategyStatus;
use App\Models\Strategy;
use Illuminate\Foundation\Http\FormRequest;

class ApproveStrategy extends FormRequest
{

    /**
     * @return bool
     */
    public function handle(): bool
    {
        $strategy = Strategy::findByUuid($this->input('id'));

        return $strategy->update([
            'status' => StrategyStatus::APPROVED
        ]);
    }
}
