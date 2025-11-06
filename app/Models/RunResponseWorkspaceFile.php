<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunResponseWorkspaceFile extends Model
{

    public $table = 'genie_run_response_workspace_file';

    protected $fillable = [
        'run_response_id',
        'workspace_file_id',
    ];

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class);
    }

    /**
     * @return BelongsTo
     */
    public function workspaceFile(): BelongsTo
    {
        return $this->belongsTo(WorkspaceFile::class);
    }
}
