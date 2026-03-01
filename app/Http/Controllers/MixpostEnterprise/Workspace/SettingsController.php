<?php

namespace App\Http\Controllers\MixpostEnterprise\Workspace;

use App\Http\Resources\MixpostEnterprise\WorkspaceResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Util;
use Inovector\MixpostEnterprise\Configs\SystemConfig;
use Inovector\MixpostEnterprise\Http\Base\Controllers\Dashboard\Workspace\SettingsController as MixpostSettingsController;
use Inovector\MixpostEnterprise\Http\Base\Requests\Dashboard\Workspace\UpdateWorkspace;

class SettingsController extends MixpostSettingsController
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
