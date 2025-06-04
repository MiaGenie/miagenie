<?php

namespace App\Console\Commands;

use App\Enums\ThreadStatus;
use App\Jobs\ThreadJob;
use App\Models\Rule;
use App\Models\Thread;
use App\Models\WorkspaceVersion;
use Illuminate\Console\Command;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Models\Workspace;


class RunStrategy extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:strategy-analysis {--workspace=}';

    /**
     * @var string
     */
    protected $description = 'Run Strategy Analysis for given workspace ID';

    /**
     * @return void
     */
    public function handle(): void
    {
        if (!$this->option('workspace')) {
            $this->info('Workspace ID is required to run strategy analysis');
            return;
        }

        $workspace = Workspace::find($this->option('workspace'));
        WorkspaceManager::setCurrent($workspace);
        $workspaceVersion = WorkspaceVersion::where('workspace_id', $this->option('workspace'))->first();

        if (!$workspace || !$workspaceVersion) {
            $this->info('Workspace ID not found');
            return;
        }

        $rule = Rule::where('version_id', $workspaceVersion->version_id)->where('rule_type', 1)->first();

        if (!$rule->rule_type) {
            $this->info('Rule not found');
            return;
        }

        $thread = Thread::create([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'status' => ThreadStatus::fromName('OPEN'),
        ]);

        ThreadJob::dispatch($thread, 'create');

        $this->info('All assistants without sync have been added to Assistant Sync Job');
    }

}
