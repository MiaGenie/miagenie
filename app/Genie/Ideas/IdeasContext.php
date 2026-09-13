<?php

namespace App\Genie\Ideas;

use App\Enums\IdeaStatus;
use App\Enums\PostFormat;
use App\Genie\Runs\PromptContext;
use App\Genie\Strategy\StrategyChannelGate;
use App\Genie\Strategy\StrategyContext;
use App\Models\AiRun;
use App\Models\Idea;
use App\Models\RuleStep;
use App\Support\PostFormats;
use Illuminate\Support\Str;

/**
 * What an ideas step's message can ask for beyond the briefing and the strategy.
 *
 * `{{{pillar}}}` and `{{{funnel}}}` are what the request was about; `{{{format}}}`,
 * `{{{channels}}}`, `{{{count}}}` and `{{{existing}}}` describe the step's own slice of it.
 */
class IdeasContext implements PromptContext
{
    /**
     * A strategy names channels by the sub-field code the admin gave them, which is conventionally
     * the Mixpost provider key but not always; the exceptions are translated here.
     *
     * @var array<string, string>
     */
    public const CHANNEL_ALIASES = [
        'facebook' => 'facebook_page',
    ];

    public function __construct(
        protected AiRun $run,
        protected RuleStep $step,
        protected IdeasRequest $request,
        protected int $share,
        protected string $locale,
    ) {}

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return [
            'pillar' => $this->pillar(),
            'funnel' => $this->funnel(),
            'format' => $this->format(),
            'channels' => $this->channels(),
            'count' => (string) $this->share,
            'existing' => $this->existing(),
        ];
    }

    /**
     * The requested pillar with everything the strategy says about it — or every pillar, when
     * none was singled out — rendered with the labels the admin gave the field.
     */
    public function pillar(): string
    {
        $strategy = $this->run->strategy;

        $pillars = $this->request->wantsAnyPillar()
            ? ContentPillars::of($strategy)
            : array_filter([ContentPillars::find($strategy, $this->request->contentPillar)]);

        if ($pillars === []) {
            return $this->request->contentPillar ?? '';
        }

        return new StrategyContext($this->run, $this->locale)
            ->renderField(ContentPillars::FIELD, array_values($pillars));
    }

    public function funnel(): string
    {
        return $this->request->wantsAnyStage()
            ? $this->text('ideas_any_stage')
            : $this->text('funnel_stage_'.$this->request->funnelStage->title());
    }

    public function format(): string
    {
        $format = $this->step->post_format;

        return $format
            ? $this->text('post_format_'.$format->title())
            : $this->text('post_format_any');
    }

    /**
     * The channels the strategy chose that can carry this step's format.
     */
    public function channels(): string
    {
        $channels = self::channelsFor(
            $this->run->strategy?->content['digital_channels'] ?? [],
            $this->step->post_format,
        );

        return implode(', ', array_map(fn (string $key) => Str::headline($key), $channels));
    }

    /**
     * The provider keys of the chosen channels that support a format; every chosen channel when
     * the format is null. A key the provider map does not know is dropped: a website is a
     * channel the strategy can name but not one a post format applies to.
     *
     * @param  array<string, mixed>  $digitalChannels  the strategy's `sub_code_name => bool` map
     * @return list<string>
     */
    public static function channelsFor(array $digitalChannels, ?PostFormat $format): array
    {
        $gate = new StrategyChannelGate;
        $keys = [];

        foreach (array_keys($digitalChannels) as $channel) {
            if (! $gate->selects($digitalChannels, (string) $channel)) {
                continue;
            }

            $provider = self::CHANNEL_ALIASES[$channel] ?? (string) $channel;

            if (! PostFormats::isKnown($provider)) {
                continue;
            }

            if ($format === null || PostFormats::supports($provider, $format)) {
                $keys[] = $provider;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * The titles of the ideas the workspace already has for this format, so the model does not
     * hand back the same ones again.
     */
    public function existing(): string
    {
        $titles = Idea::query()
            ->where('workspace_id', $this->run->workspace_id)
            ->where('status', '!=', IdeaStatus::TRASH)
            ->when($this->step->post_format, fn ($query, $format) => $query->where('post_format', $format))
            ->latest()
            ->limit(100)
            ->pluck('theme');

        if ($titles->isEmpty()) {
            return $this->text('ideas_no_existing');
        }

        return $titles->map(fn (string $title) => '- '.$title)->implode("\n");
    }

    protected function text(string $key): string
    {
        return (string) __('genie.'.$key, [], $this->locale);
    }
}
