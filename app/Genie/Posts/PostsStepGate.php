<?php

namespace App\Genie\Posts;

use App\Enums\Modality;
use App\Enums\PostFormat;
use App\Enums\RuleSubType;
use App\Genie\Runs\PostFormatGate;
use App\Genie\Runs\StepGate;
use App\Models\AiRun;
use App\Models\RuleStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Which steps of a posts rule apply to a run.
 *
 * Each posts sub type is one prompt for one job, so the request decides which run: the first
 * generation takes the content and image steps, a caption regeneration (`only = text`) takes the
 * revise-text step alone, an images-only run takes the image step alone. The two image revision
 * sub types are prompts for a job on one slot and never run here.
 *
 * Each sub type contributes at most one step per run (the step itself may make several calls —
 * the image step draws once per brief): when a rule has both a generic step and one naming the
 * idea's format, the format-specific one wins and the other is passed over; a step naming another
 * format never applies. An image step is also passed over for a format that carries no images (a
 * video). There is no fallback: a run with nothing to execute is refused upstream.
 */
class PostsStepGate implements StepGate
{
    public function __construct(protected PostFormatGate $formats = new PostFormatGate) {}

    public function passes(AiRun $run, RuleStep $step): bool
    {
        $request = PostsRequest::fromRun($run);
        $subType = $step->rule_sub_type;

        if (! $subType?->runsInPostsRun()) {
            return $this->skip($step, 'its sub type does not run in a posts run');
        }

        $requested = PostFormatGate::requested($run);

        $chosen = self::select($run->rule?->steps ?? new Collection, $subType, $requested);

        if (! $chosen || ! $chosen->is($step)) {
            return false;
        }

        if ($subType->modality() === Modality::IMAGE) {
            if ($requested && $requested->imageRange() === null) {
                return false;
            }

            return $request->wantsImages();
        }

        return $request->isRegeneration()
            ? $subType === RuleSubType::POSTS_REVISE_TEXT && $request->wantsText()
            : $subType === RuleSubType::POSTS_CONTENT;
    }

    /**
     * The steps this run will actually execute, in rule order.
     *
     * @return Collection<int, RuleStep>
     */
    public function generators(AiRun $run): Collection
    {
        $steps = $run->rule?->steps ?? new Collection;

        return $steps
            ->filter(fn (RuleStep $step) => $this->passes($run, $step))
            ->sortBy('position')
            ->values();
    }

    /**
     * The one step of a sub type that applies to a format: the step naming that format when the
     * rule has one, else the step naming no format; never a step naming another format. Ties go
     * to the lowest position. Shared with the revision templates, so a fix or rethink follows the
     * same override rule as the run.
     *
     * @param  Collection<int, RuleStep>  $steps
     */
    public static function select(Collection $steps, RuleSubType $subType, ?PostFormat $format): ?RuleStep
    {
        $candidates = $steps
            ->filter(fn (RuleStep $step) => $step->rule_sub_type === $subType)
            ->sortBy('position');

        if ($format) {
            $specific = $candidates->first(fn (RuleStep $step) => $step->post_format === $format);

            if ($specific) {
                return $specific;
            }
        }

        return $candidates->first(fn (RuleStep $step) => $step->post_format === null);
    }

    /**
     * A step that cannot apply is a misconfigured rule, not a broken run: passed over, reason logged.
     */
    protected function skip(RuleStep $step, string $reason): bool
    {
        Log::warning('Genie posts step skipped: '.$reason, [
            'step_id' => $step->id,
            'rule_sub_type' => $step->rule_sub_type?->name,
        ]);

        return false;
    }
}
