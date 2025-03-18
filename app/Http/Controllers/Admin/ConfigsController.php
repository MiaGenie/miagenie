<?php

namespace App\Http\Controllers\Admin;

use App\Configs\OpenAIConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ConfigsController extends Controller
{
    /**
     * @return Response
     */
    public function form(): Response
    {
        return Inertia::render('Genie/Admin/Config/OpenAIConfig', [
            'configs' => (new OpenAIConfig())->all()
        ]);
    }

    /**
     * @param OpenAIConfig $openAIConfig
     * @return RedirectResponse
     */
    public function update(OpenAIConfig $openAIConfig): RedirectResponse
    {
        $openAIConfig->save();

        return redirect()->back();
    }
}
