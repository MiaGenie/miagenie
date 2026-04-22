<?php

namespace App\Listeners;

use App\Models\Version;
use App\Models\WorkspaceVersion;
use Inovector\MixpostEnterprise\Events\Workspace\WorkspaceCreated;

class AssignVersionToWorkspace
{

    public function handle(WorkspaceCreated $workspaceCreated): void
    {
        if ($workspaceCreated->workspace->workspaceVersion?->version_id) {
            return;
        }

        $defaultVersion = Version::where('is_default', true)->firstOrFail();

        WorkspaceVersion::create([
            'workspace_id' => $workspaceCreated->workspace->id,
            'version_id' => $defaultVersion->id,
        ]);
    }
}
