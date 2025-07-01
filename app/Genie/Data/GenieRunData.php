<?php

namespace App\Genie\Data;

use App\Concerns\GenieParser;
use App\Enums\GenieSyncAction;
use App\Enums\VersionGroupType;
use App\Models\Briefing;
use App\Models\Competitor;
use App\Models\RuleStep;
use App\Models\RunCompetitor;
use App\Models\Strategy;
use App\Models\Run;
use App\Models\RunResponse;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Arr;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieRunData
{
    use GenieParser;

    protected const TYPE = 'RUN';

    /**
     * @var Run
     */
    private Run $run;

    /**
     * @var GenieSyncAction
     */
    private GenieSyncAction $action;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $lastStep;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $nextStep;

    /**
     * @param Run $model
     * @param GenieSyncAction $action
     */
    public function __construct(
        Run $model,
        GenieSyncAction $action,
    ) {
        $this->run = $model;
        WorkspaceManager::setCurrent($this->run->workspace);
        $this->lastStep = $this->lastStep();
        $this->nextStep = $this->nextStep($this->lastStep);
    }

    public function nextAction(): string
    {
        switch ($this->action) {
            case 'create':
                $this->nextAction = 'update';
                break;
            case 'update':
                $this->nextAction = 'status';
                break;
            case 'status':
                switch (strtoupper($this->response['status'])) {
                    default:
                    case 'QUEUED':
                    case 'IN_PROGRESS':
                        $this->nextAction = 'status';
                        break;
                    case 'COMPLETED':
                        $this->nextAction = 'message';
                        break;
                }
                break;
            case 'message':
                $nextStep = $this->nextStep($this->nextStep);
                $this->nextAction = $nextStep ? 'update' : '';
                break;
        }

        return $this->nextAction;
    }

    /**
     * @return ?RuleStep
     */
    public function lastStep(): ?RuleStep
    {
        if (isset($this->lastStep)) {
            return $this->lastStep;
        }

        $lastRunResponse = $this->getLastRunResponse();
        $this->lastStep = $lastRunResponse?->id ? RuleStep::find($lastRunResponse?->step_id) : null;

        return $this->lastStep;
    }

    /**
     * @param ?RuleStep $lastStep
     * @return ?RuleStep
     */
    public function nextStep(?RuleStep $lastStep = null): ?RuleStep
    {
        if (isset($this->nextStep)) {
            return $this->nextStep;
        }

        $this->nextStep = match ($lastStep?->rule_sub_type->name) {
            null, 'BRIEFINGS' => $this->getNextStep(),
            'COMPETITORS' => $this->getNextStepAfterCompetitor()
        };

        return $this->nextStep;
    }

    /**
     * @return ?RunResponse
     */
    private function getLastRunResponse(): ?RunResponse
    {
        return RunResponse::where(['run_id' => $this->run->id])->latest('id')->first();
    }

    /**
     * @return ?RuleStep
     */
    private function getNextStepAfterCompetitor(): ?RuleStep
    {
        $todoCompetitors = $this->getTodoCompetitors($this->lastStep);
        if ($todoCompetitors->count() === 0) {
            return $this->getNextStep();
        }
        return $this->lastStep;
    }

    /**
     * @return ?Competitor
     */
    public function getNextCompetitor(): ?Competitor
    {
        //TODO - check when both nulls
        $todoCompetitors = $this->getTodoCompetitors($this->nextStep);
        return Competitor::find($todoCompetitors->first());
    }

    /**
     * @return Collection
     */
    private function getCompetitorIds(): Collection
    {
        //TODO - assure we only get completely filled competitors - check out laravel model features
        return Competitor::where('workspace_id', $this->run->workspace_id)->pluck('id');
    }

    /**
     * @param RuleStep $step
     * @return Collection
     */
    private function getDoneCompetitors(RuleStep $step): Collection
    {
        $stepRunResponses = $this->run->runResponses->where('step_id', $step->id)->pluck('id')->toArray();
        $stepRunCompetitors = RunCompetitor::whereIn('run_response_id', $stepRunResponses)->pluck('competitor_id');
        return Competitor::whereIn('id', $stepRunCompetitors)->pluck('id');
    }

    /**
     * @param RuleStep $step
     * @return ?Collection
     */
    private function getTodoCompetitors(RuleStep $step): ?Collection
    {
        $allCompetitors = $this->getCompetitorIds();
        $doneCompetitors = $this->getDoneCompetitors($step);
        return $allCompetitors->diff($doneCompetitors);
    }

    /**
     * @param ?int $lastPosition
     * @return ?RuleStep
     */
    private function getNextStep(?int $lastPosition = null): ?RuleStep
    {
        $lastPosition ??= $this->getLastPosition();
        $nextStep = $this->getNextStepByPosition($lastPosition);
        if ($nextStep?->rule_sub_type->name === 'COMPETITORS' && $this->getCompetitorIds()->count() === 0) {
            $lastPosition++;
            return $this->getNextStep($lastPosition);
        } else {
            return $nextStep;
        }
    }

    /**
     * @param int $lastPosition
     * @return ?RuleStep
     */
    private function getNextStepByPosition(int $lastPosition): ?RuleStep
    {
        return RuleStep::where(
            [
                ['rule_id', $this->run->rule_id],
                ['position', '>', $lastPosition],
            ]
        )->oldest('position')->first();
    }

    /**
     * @return int
     */
    private function getLastPosition(): int
    {
        return $this->lastStep?->position ?? 0;
    }

    /**
     * @return string
     */
    private function getMessage(): string
    {
        $replacements = $this->getReplacements();

        return $this->parseContent($this->nextStep->message, $replacements);
    }

    /**
     * @return array
     */
    private function getReplacements(): array
    {
        $briefings = $this->getBriefingReplacements();
        $competitors = $this->nextStep->rule_sub_type->name === 'COMPETITORS' ? $this->getCompetitorReplacements() : [];
        $strategy = $this->getStrategyReplacements();

        return array_merge($briefings, $competitors, $strategy);
    }

    /**
     * @return array
     */
    private function getBriefingReplacements(): array
    {
        $briefing = Briefing::where(['workspace_id' => $this->run->workspace_id])->latest()->first()?->content;
        $briefing = $this->translateFieldOptions($briefing, 'BRIEFINGS');

        return Arr::prependKeysWith($briefing, 'briefings.');
    }

    /**
     * @return array
     */
    private function getCompetitorReplacements(): array
    {
        $competitor = $this->getNextCompetitor()->content;
        $competitor = $this->translateFieldOptions($competitor, 'COMPETITORS');

        return Arr::prependKeysWith($competitor, 'competitors.');
    }

    /**
     * @return array
     */
    private function getStrategyReplacements(): array
    {
        $strategy = Strategy::where(['workspace_id' => $this->run->workspace_id])->latest()->first()?->content;

        return $strategy ? Arr::prependKeysWith(
            Strategy::where(['workspace_id' => $this->run->workspace_id])->latest()->first()?->content,
            'strategy.'
        ) : [];
    }

    /**
     * @param array $content
     * @param string $type
     * @return array
     */
    private function translateFieldOptions(array $content, string $type): array
    {
        foreach ($content as $key => $item) {
            $content[$key] = is_array($item) ? $this->getTranslatedFieldOptions($key, $item, $type) : $item;
        }
        return $content;
    }

    /**
     * @param string $key
     * @param array $item
     * @param string $type
     * @return string
     */
    private function getTranslatedFieldOptions(string $key, array $item, string $type): string
    {
        $field = VersionField::where([
            'version_id' => $this->run->rule->version_id,
            'group_type' => VersionGroupType::fromName($type),
            'code_name' => $key,
        ])->first();

        $fieldOptions = VersionFieldOption::where('field_id', $field->id)->get()
            ->pluck('name', 'code_name')->toArray();

        if (sizeof($item) === 1) {
            return $fieldOptions[$item[0]];
        }

        $item = array_map(function ($value) use ($fieldOptions) {
            return $fieldOptions[$value];
        }, $item);

        return implode(', ', $item);
    }

}
