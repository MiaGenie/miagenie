<?php

namespace App\Http\Requests\MixpostEnterprise\Panel\Workspace;

use App\Enums\StrategyRegenerationMode;
use App\Genie\Strategy\StrategyRegeneration;
use App\Genie\Strategy\StrategyRegenerationException;
use App\Models\Strategy;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegenerateStrategy extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(StrategyRegenerationMode::class)],
        ];
    }

    /**
     * @throws StrategyRegenerationException
     */
    public function handle(): Strategy
    {
        return app(StrategyRegeneration::class)->request(
            Workspace::firstOrFailByUuid($this->route('workspace')),
            $this->mode(),
        );
    }

    public function mode(): StrategyRegenerationMode
    {
        return StrategyRegenerationMode::from($this->input('mode'));
    }
}
