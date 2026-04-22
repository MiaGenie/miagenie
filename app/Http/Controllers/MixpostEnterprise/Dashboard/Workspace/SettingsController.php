<?php

namespace App\Http\Controllers\MixpostEnterprise\Dashboard\Workspace;

use App\Http\Requests\MixpostEnterprise\Dashboard\Workspace\UpdateWorkspace;
use App\Http\Resources\MixpostEnterprise\WorkspaceResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Util;
use Inovector\MixpostEnterprise\Configs\SystemConfig;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('MixpostEnterprise/Dashboard/Workspace/Settings', [
            'workspace' => new WorkspaceResource(WorkspaceManager::current()),
            'allow_workspace_service' => [
                'twitter' => app(SystemConfig::class)->allowWorkspaceTwitterService()
            ],
            'locales' => Util::config('locales')
        ]);
    }

    public function update(UpdateWorkspace $updateWorkspace): RedirectResponse
    {
        $updateWorkspace->handle();

        return redirect()->back();
    }
}
