<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;
use Illuminate\Support\Collection;

enum RuleSubType: int
{
    use FromName;
    use WithTitle;

    case BRIEFINGS = 12;
    case BRIEFINGS_MULTIPLE = 13;
    case CHANNELS = 14;
    case IDEAS_INITIAL = 21;
    case IDEAS_FORMAT = 22;
    case IDEAS_MULTIPLE = 23;

    /*
     * The admin step editor offers the sub types whose value starts with the rule type's value
     * (RuleType::POSTS = 5), which is what fixes these numbers. Each posts sub type is one prompt
     * for one job, and implies its modality: CONTENT writes the first caption, IMAGE draws one
     * picture per brief, REVISE_TEXT rewrites the caption from feedback, IMAGE_FIX redoes one
     * picture with the previous one attached, IMAGE_RETHINK redraws one picture from a brief. A
     * posts step applies to every idea unless it names a post format. There is no primer step and
     * no fallback: an action exists on the post only when the rule has its step.
     */
    case POSTS_CONTENT = 52;
    case POSTS_IMAGE = 53;
    case POSTS_REVISE_TEXT = 54;
    case POSTS_IMAGE_FIX = 55;
    case POSTS_IMAGE_RETHINK = 56;

    public function title(): string
    {
        return match ($this) {
            self::BRIEFINGS => 'briefings',
            self::BRIEFINGS_MULTIPLE => 'briefings_multiple',
            self::CHANNELS => 'channels',
            self::IDEAS_INITIAL => 'ideas_initial',
            self::IDEAS_FORMAT => 'ideas_format',
            self::IDEAS_MULTIPLE => 'ideas_multiple',
            self::POSTS_CONTENT => 'posts_content',
            self::POSTS_IMAGE => 'posts_image',
            self::POSTS_REVISE_TEXT => 'posts_revise_text',
            self::POSTS_IMAGE_FIX => 'posts_image_fix',
            self::POSTS_IMAGE_RETHINK => 'posts_image_rethink',
        };
    }

    /**
     * A step that only primes the conversation with context and produces nothing to store.
     */
    public function isContext(): bool
    {
        return match ($this) {
            self::IDEAS_INITIAL => true,
            default => false,
        };
    }

    /**
     * A step that generates the run's output — ideas for a format, a post's caption or images —
     * rather than priming the conversation.
     */
    public function isGenerator(): bool
    {
        return match ($this) {
            self::IDEAS_FORMAT,
            self::POSTS_CONTENT, self::POSTS_IMAGE, self::POSTS_REVISE_TEXT, self::POSTS_IMAGE_FIX, self::POSTS_IMAGE_RETHINK => true,
            default => false,
        };
    }

    /**
     * Whether a generator step must name the post format it generates for.
     *
     * An ideas step does: its schema and message are written per format. A posts step need not:
     * the idea carries the format, and a step without one applies to every idea.
     */
    public function requiresPostFormat(): bool
    {
        return $this === self::IDEAS_FORMAT;
    }

    /**
     * What a step of this sub type produces. The sub type decides, not the admin: the posts image
     * sub types draw, everything else — context steps, the strategy and ideas generators, the
     * posts text steps — writes.
     */
    public function modality(): Modality
    {
        return match ($this) {
            self::POSTS_IMAGE, self::POSTS_IMAGE_FIX, self::POSTS_IMAGE_RETHINK => Modality::IMAGE,
            default => Modality::TEXT,
        };
    }

    /**
     * The posts steps a run executes: the first-generation pair and the caption rewrite. The two
     * image revisions are prompts for a job on one slot, never a run step.
     */
    public function runsInPostsRun(): bool
    {
        return match ($this) {
            self::POSTS_CONTENT, self::POSTS_IMAGE, self::POSTS_REVISE_TEXT => true,
            default => false,
        };
    }

    /**
     * The sub types of a rule type as withTitle() rows, plus what the step editor needs to know:
     * whether the step generates (so its schema is fixed in code), whether it must name a format,
     * and whether it is an image step.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function forEditor(int $ruleTypeValue): Collection
    {
        return self::withTitle('', (string) $ruleTypeValue)
            ->map(function (Collection $row) {
                $case = self::from($row['value']);

                return $row->merge([
                    'generator' => $case->isGenerator(),
                    'format_required' => $case->requiresPostFormat(),
                    'image' => $case->modality() === Modality::IMAGE,
                ]);
            })
            ->values();
    }
}
