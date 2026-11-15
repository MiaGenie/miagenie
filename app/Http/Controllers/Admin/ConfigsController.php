<?php

namespace App\Http\Controllers\Admin;

use App\Abstracts\Config;
use App\Configs\AnthropicConfig;
use App\Configs\DeepSeekConfig;
use App\Configs\GeminiConfig;
use App\Configs\OpenAIConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ConfigsController extends Controller
{
    public function form(): Response
    {
        return Inertia::render('Genie/Admin/Config/Index', [
            'configs' => array_map(fn (Config $config) => $config->all(), $this->configs()),
        ]);
    }

    /**
     * Every group shares field names (`api_key`), so the payload is nested by group. A group
     * missing from the request is left alone rather than saved empty.
     */
    public function update(Request $request): RedirectResponse
    {
        foreach ($this->configs() as $group => $config) {
            if (! $request->has($group)) {
                continue;
            }

            $config->save((array) $request->input($group, []));
        }

        return redirect()->back();
    }

    /**
     * The provider configs editable on this page, keyed by their group.
     *
     * @return array<string, Config>
     */
    private function configs(): array
    {
        return collect([OpenAIConfig::class, AnthropicConfig::class, GeminiConfig::class, DeepSeekConfig::class])
            ->map(fn (string $class) => app($class))
            ->keyBy(fn (Config $config) => $config->group())
            ->all();
    }
}
