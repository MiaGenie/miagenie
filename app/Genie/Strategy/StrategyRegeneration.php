<?php

namespace App\Genie\Strategy;

use App\Enums\BriefingStatus;
use App\Enums\StrategyRegenerationMode;
use App\Enums\StrategyStatus;
use App\Models\Briefing;
use App\Models\Strategy;
use App\Models\Workspace;
use App\Models\WorkspaceVersion;
use Illuminate\Support\Facades\DB;

/**
 * An admin asking for a workspace's strategy to be generated again.
 *
 * Nothing is generated here, and nothing is overwritten: the previous strategy, its run and the
 * ideas built from it stay as they are. What is created is an OPEN strategy with no run, which the
 * strategy page reads as "ready to generate" and offers the customer the button for; the
 * customer's generate then attaches the run to it. Ideas keep using the newest approved strategy
 * until the new one is approved.
 *
 * Runs from the admin panel, where no workspace is current, so every query names its workspace.
 */
class StrategyRegeneration
{
    /**
     * @throws StrategyRegenerationException when a run is still producing the latest strategy, or
     *                                       when a briefing is to be cloned and there is none
     */
    public function request(Workspace $workspace, StrategyRegenerationMode $mode): Strategy
    {
        $latest = $this->latestStrategy($workspace);

        if ($latest?->isGenerating()) {
            throw StrategyRegenerationException::generating();
        }

        $briefing = $this->latestBriefing($workspace);

        if ($mode->clonesBriefing() && ! $briefing) {
            throw StrategyRegenerationException::noBriefing();
        }

        return DB::transaction(function () use ($workspace, $mode, $latest, $briefing): Strategy {
            if ($mode->createsBriefing()) {
                $this->createBriefing($workspace, $mode->clonesBriefing() ? $briefing->content : null);
            }

            // Asking twice must not stack empty strategies: the one already waiting is reused.
            if ($latest?->awaitsGeneration()) {
                return $latest;
            }

            return Strategy::create([
                'workspace_id' => $workspace->id,
                'status' => StrategyStatus::OPEN,
            ]);
        });
    }

    /**
     * Where the workspace's strategy stands, for the admin's buttons.
     *
     * @return array{state: 'none'|'awaiting_generation'|'generating'|'pending_approval'|'approved'|'error'|'other', has_briefing: bool}
     */
    public function state(Workspace $workspace): array
    {
        $latest = $this->latestStrategy($workspace);

        return [
            'state' => match (true) {
                ! $latest => 'none',
                $latest->awaitsGeneration() => 'awaiting_generation',
                $latest->isGenerating() => 'generating',
                $latest->status === StrategyStatus::PENDING_APPROVAL => 'pending_approval',
                $latest->status === StrategyStatus::APPROVED => 'approved',
                $latest->status === StrategyStatus::ERROR => 'error',
                default => 'other',
            },
            'has_briefing' => (bool) $this->latestBriefing($workspace),
        ];
    }

    /**
     * A new briefing on the workspace's current version, left as a draft so the customer goes
     * through the wizard and finishes it before generating; the previous briefing stays with the
     * run that used it.
     *
     * @param  array<string, mixed>|null  $content
     */
    protected function createBriefing(Workspace $workspace, ?array $content): Briefing
    {
        $briefing = new Briefing([
            'content' => $content,
            'version_id' => WorkspaceVersion::where('workspace_id', $workspace->id)->firstOrFail()->version_id,
            'status' => BriefingStatus::DRAFT,
        ]);

        $briefing->workspace_id = $workspace->id;
        $briefing->save();

        return $briefing;
    }

    protected function latestStrategy(Workspace $workspace): ?Strategy
    {
        return Strategy::byWorkspace($workspace)->latest('id')->first();
    }

    protected function latestBriefing(Workspace $workspace): ?Briefing
    {
        return Briefing::byWorkspace($workspace)->latest('id')->first();
    }
}
