<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Inovector\MixpostEnterprise\Models\Workspace as WorkspaceEnterpriseModel;

use const pcov\version;

class Workspace extends WorkspaceEnterpriseModel
{

    protected $fillable = [
        'uuid',
        'name',
        'hex_color',
        'owner_id',
        'access_status',
        'generic_subscription_plan_id',
        'generic_subscription_free',
        'generic_trial_ends_at',
        'pm_type',
        'pm_card_brand',
        'pm_card_last_four',
        'pm_card_expires',
        'limits',
        'locale',
    ];

    public function workspaceVersion(): HasOne
    {
        return $this->hasOne(WorkspaceVersion::class, 'workspace_id', 'id');
    }

    public function version(): hasOneThrough
    {
        return $this->hasOneThrough(Version::class, WorkspaceVersion::class, 'workspace_id', 'id', 'id', 'version_id');
    }
}
