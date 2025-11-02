<?php

namespace App\Concerns\Controller;

use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Jobs\RunDraftJob;
use App\Models\Idea;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Strategy;
use App\Models\WorkspaceVersion;
use Illuminate\Database\Eloquent\Collection;
use Inovector\Mixpost\Facades\WorkspaceManager;

trait DraftGeneration
{

    /**
     * @param Collection<Idea> $ideas
     */
    private function draftGeneration(Collection $ideas): void
    {
        $workspace = WorkspaceManager::current();
        $workspaceVersion = WorkspaceVersion::where('workspace_id', $workspace->id)->first();

        $rule = Rule::where('version_id', $workspaceVersion->version_id)->where('rule_type', RuleType::DRAFTS)->first();

        $run = Run::create([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'status' => RunStatus::OPEN,
        ]);

        $strategy = Strategy::whereHas('workspace', function ($query) use ($workspace) {
            $query->where('id', $workspace->id);
        })->latest()->first();

        $run->runStrategy()->create([
            'strategy_id' => $strategy->id
        ]);

        $ideas->each(function (Idea $idea) use ($run) {
            $run->runIdeas()->create(['idea_id' => $idea->id]);
        });

        RunDraftJob::dispatch($run, GenieSyncAction::CREATE);

    }
}
