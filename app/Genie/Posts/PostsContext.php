<?php

namespace App\Genie\Posts;

use App\Genie\Images\ImageContext;
use App\Genie\Runs\PromptContext;
use App\Models\AiRun;
use App\Models\GeniePost;
use App\Models\Idea;
use App\Models\RuleStep;
use Inovector\Mixpost\Facades\SocialProviderManager;

/**
 * What a posts step's message can ask for beyond the briefing and the strategy: the idea the
 * post is made from, field by field or as one block, the post as it stands, and the reviewer's
 * feedback when the run is a regeneration.
 */
class PostsContext implements PromptContext
{
    /**
     * The idea's texts, in the order they read, keyed by the `genie.idea_*` label they carry.
     *
     * @var list<string>
     */
    public const TEXTS = ['theme', 'objective', 'suggested_content', 'multimedia_suggestion', 'production_instructions'];

    protected ?GeniePost $post;

    protected ?Idea $idea;

    public function __construct(
        protected AiRun $run,
        protected RuleStep $step,
        protected PostsRequest $request,
        protected string $locale,
        ?GeniePost $post = null,
        protected ?ImageContext $image = null,
    ) {
        $this->post = $post ?? GeniePost::withoutWorkspace()->find($request->post);
        $this->idea = $this->post?->idea;
    }

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        $values = [];

        foreach (self::TEXTS as $name) {
            $values['idea.'.$name] = (string) ($this->idea?->{$name} ?? '');
        }

        $values['idea.funnel_stage'] = $this->funnelStage();
        $values['idea.content_pillar'] = (string) ($this->idea?->content_pillar ?? '');
        $values['idea.post_format'] = $this->postFormat();
        $values['idea.publish_to'] = $this->publishTo();
        $values['idea.images'] = $this->images();
        $values['idea.FULL'] = $this->full();
        $values['post.caption'] = $this->caption();
        $values['post.title'] = $this->title();
        $values['feedback'] = $this->image?->feedback ?? $this->request->feedback ?? '';
        $values['idea.IMAGE'] = $this->image?->brief ?? '';
        $values['idea.IMAGE#'] = $this->image ? (string) $this->image->position : '';
        $values['idea.IMAGES#'] = (string) count($this->briefs());

        if ($this->image) {
            $values = array_merge($values, $this->image->values());
        }

        return $values;
    }

    /**
     * The whole idea as one labelled block, for a step that wants everything rather than fields.
     */
    public function full(): string
    {
        $blocks = [];

        foreach (self::TEXTS as $name) {
            $value = (string) ($this->idea?->{$name} ?? '');

            if (filled($value)) {
                $blocks[] = $this->label($name).': '.$value;
            }
        }

        foreach ([
            'funnel_stage' => $this->funnelStage(),
            'content_pillar' => (string) ($this->idea?->content_pillar ?? ''),
            'post_format' => $this->postFormat(),
            'publish_to' => $this->publishTo(),
            'images' => $this->images(),
        ] as $name => $value) {
            if (filled($value)) {
                $blocks[] = $this->label($name).': '.$value;
            }
        }

        return implode("\n\n", $blocks);
    }

    public function funnelStage(): string
    {
        $stage = $this->idea?->funnel_stage;

        return $stage ? $this->text('funnel_stage_'.$stage->title()) : '';
    }

    public function postFormat(): string
    {
        $format = $this->idea?->post_format;

        return $format ? $this->text('post_format_'.$format->title()) : '';
    }

    /**
     * The providers the idea is meant for, by the names Mixpost shows.
     */
    public function publishTo(): string
    {
        $providers = SocialProviderManager::providers();

        return implode(', ', array_map(
            fn (string $key) => isset($providers[$key]) ? $providers[$key]::name() : $key,
            $this->idea?->publish_to ?? [],
        ));
    }

    /**
     * The image briefs as a numbered list.
     */
    public function images(): string
    {
        $lines = [];

        foreach ($this->briefs() as $index => $brief) {
            $lines[] = ($index + 1).'. '.$brief;
        }

        return implode("\n", $lines);
    }

    /**
     * The idea's image briefs that say something, in slide order.
     *
     * @return list<string>
     */
    public function briefs(): array
    {
        return array_values(array_filter(
            $this->idea?->images ?? [],
            fn ($brief) => is_string($brief) && filled($brief),
        ));
    }

    /**
     * The caption the post carries now, as plain text; empty before the first text step wrote it.
     */
    public function caption(): string
    {
        $content = $this->post?->originalVersion()?->content ?? [];

        return MixpostCaption::toText($content[0]['body'] ?? null);
    }

    public function title(): string
    {
        return MixpostVersionOptions::titleOf($this->post?->originalVersion()?->options) ?? '';
    }

    protected function label(string $name): string
    {
        return $this->text($name === 'theme' ? 'idea_title' : 'idea_'.$name);
    }

    protected function text(string $key): string
    {
        return (string) __('genie.'.$key, [], $this->locale);
    }
}
