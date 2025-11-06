<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Models\Workspace;


class WorkspaceVersion extends Model
{
    use OwnedByWorkspace;
    use HasUuid;

    /**
     * @var string
     */
    protected $table = 'genie_workspaces_versions';

    /**
     * @var string
     */
    protected $primaryKey = 'workspace_id';

    /**
     * @var string[]
     */
    protected $fillable = [
        'workspace_id',
        'version_id'
    ];

    /**
     * @return HasOne
     */
    public function workspace(): HasOne
    {
        return $this->hasOne(Workspace::class, 'id', 'workspace_id');
    }

    /**
     * @return HasOne
     */
    public function version(): HasOne
    {
        return $this->hasOne(Version::class, 'id', 'version_id');
    }

    /**
     * @return HasManyThrough
     */
    public function fields(): HasManyThrough
    {
        return $this->through('version')->has('fields');
    }

    /**
     * @return HasManyThrough
     */
    public function options(): HasManyThrough
    {
        return $this->through('fields')->has('options');
    }
}
