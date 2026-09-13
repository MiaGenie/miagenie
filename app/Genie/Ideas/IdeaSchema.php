<?php

namespace App\Genie\Ideas;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
use App\Genie\Runs\SchemaDefinition;
use App\Models\AiRun;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * The structured output of an ideas step.
 *
 * Fixed by the Idea model rather than drawn by the admin: the same five texts are what a post is
 * later produced from, so their shape is not a per-version decision. What does vary per run is
 * how many ideas the step owes, which stages it may pick from and which pillars it may name —
 * those are enums here, so the request is enforced by the contract rather than by the prose.
 */
class IdeaSchema implements SchemaDefinition
{
    public const ROOT = 'ideas';

    /**
     * The texts every idea carries, in the order they are asked for.
     *
     * @var list<string>
     */
    public const TEXTS = ['title', 'objective', 'suggested_content', 'multimedia_suggestion', 'production_instructions'];

    /**
     * @param  list<string>  $pillarTitles  the pillars the model may name; empty leaves it free text
     * @param  PostFormat|null  $format  the step's format, which decides whether — and how many —
     *                                   per-image briefs are asked for
     */
    public function __construct(
        protected FunnelStage $stage,
        protected array $pillarTitles,
        protected int $share,
        protected string $locale,
        protected ?PostFormat $format = null,
    ) {}

    /**
     * The schema for one generator step of a run: the pillar the customer chose, or all of the
     * strategy's; the stage they chose, or all of them; this step's share of the total; and the
     * image briefs the step's format calls for.
     */
    public static function forRun(AiRun $run, IdeasRequest $request, int $share, string $locale, ?PostFormat $format = null): self
    {
        $titles = $request->wantsAnyPillar()
            ? ContentPillars::titles($run->strategy)
            : [$request->contentPillar];

        return new self($request->funnelStage, $titles, $share, $locale, $format);
    }

    /**
     * The shape with nothing requested and no format, for an admin preview of an ideas step. The
     * per-image briefs only appear on the schema a run sends, since they depend on the format.
     */
    public static function preview(string $locale): self
    {
        return new self(FunnelStage::GLOBAL, [], 1, $locale);
    }

    /**
     * @return array<string, Type>
     */
    public function properties(JsonSchema $schema): array
    {
        $count = max(1, $this->share);

        return [
            self::ROOT => $schema->array()
                ->items($schema->object($this->idea($schema)))
                ->min($count)
                ->max($count)
                ->title($this->text('ideas'))
                ->description($this->text('ideas_schema_desc'))
                ->required(),
        ];
    }

    /**
     * Strict mode lists every property as required, so an optional one is nullable instead.
     *
     * @return array<string, Type>
     */
    protected function idea(JsonSchema $schema): array
    {
        $properties = [];

        foreach (self::TEXTS as $name) {
            $properties[$name] = $schema->string()
                ->title($this->text('idea_'.$name))
                ->description($this->text('idea_'.$name.'_desc'))
                ->required();
        }

        $properties['funnel_stage'] = $schema->string()
            ->enum($this->stageTitles())
            ->title($this->text('funnel_stage'))
            ->description($this->text('idea_funnel_stage_desc'))
            ->required();

        $pillar = $schema->string()
            ->title($this->text('content_pillar'))
            ->description($this->text('idea_content_pillar_desc'));

        $properties['content_pillar'] = $this->pillarTitles === []
            ? $pillar->required()->nullable()
            : $pillar->enum($this->pillarTitles)->required();

        // Only a format that is a set of images asks for briefs; strict mode would otherwise force
        // an empty list onto a video idea.
        if ($range = $this->format?->imageRange()) {
            [$min, $max] = $range;

            $properties['images'] = $schema->array()
                ->items($schema->string())
                ->min($min)
                ->max($max)
                ->title($this->text('idea_images'))
                ->description($this->text('idea_images_desc'))
                ->required();
        }

        return $properties;
    }

    /**
     * @return list<string>
     */
    protected function stageTitles(): array
    {
        $stages = $this->stage->isAny() ? FunnelStage::concrete() : [$this->stage];

        return array_map(fn (FunnelStage $stage) => $stage->title(), $stages);
    }

    protected function text(string $key): string
    {
        return (string) __('genie.'.$key, [], $this->locale);
    }
}
