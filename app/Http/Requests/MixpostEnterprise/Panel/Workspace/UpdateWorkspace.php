<?php

namespace App\Http\Requests\MixpostEnterprise\Panel\Workspace;

use Inovector\Mixpost\Enums\WorkspaceUserRole;
use App\Models\Workspace;

class UpdateWorkspace extends WorkspaceFormRequest
{
    public function handle(): bool
    {
        $workspace = Workspace::firstOrFailByUuid($this->route('workspace'));

        if ($this->input('owner_id')) {
            if ($workspace->users()->where('user_id', $this->input('owner_id'))->doesntExist()) {
                $workspace->attachUser($this->input('owner_id'), WorkspaceUserRole::ADMIN);
            }
        }

        return $workspace->update($this->requestData());
    }
}
