<?php

namespace App\Http\Requests\MixpostEnterprise\Panel\Workspace;

use Inovector\Mixpost\Enums\WorkspaceUserRole;
use Inovector\MixpostEnterprise\Events\Workspace\WorkspaceCreated;
use Inovector\MixpostEnterprise\Http\Base\Requests\Panel\Workspace\StoreWorkspace as MixpostStoreWorkspace;
use App\Models\Workspace;

class StoreWorkspace extends MixpostStoreWorkspace
{
    public function handle(): null|Workspace
    {
        $workspace = Workspace::create($this->requestData());

        if ($this->input('owner_id')) {
            $workspace->attachUser(
                id: $this->input('owner_id'),
                role: WorkspaceUserRole::ADMIN,
                canApprove: true
            );
        }

        WorkspaceCreated::dispatch($workspace, true);

        return $workspace;
    }
}
