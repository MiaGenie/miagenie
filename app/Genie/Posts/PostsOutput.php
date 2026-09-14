<?php

namespace App\Genie\Posts;

use App\Genie\Runs\StepOutput;
use App\Models\AiRunStep;
use App\Models\GeniePost;
use Inovector\Mixpost\Events\Post\PostUpdated;
use Inovector\Mixpost\Models\PostVersion;
use RuntimeException;

/**
 * Stores a text step's caption and title on the Mixpost post's original version.
 *
 * Only the body of the first content item and the two title options change; the media the
 * version carries, its URL, video thumbs and every other provider option stay as the editor
 * left them. Status is the run state's business.
 */
class PostsOutput implements StepOutput
{
    /**
     * @param  array<string, mixed>  $structured
     */
    public function write(AiRunStep $runStep, array $structured): void
    {
        $resolved = $this->resolve($runStep, $structured);

        $post = GeniePost::withoutWorkspace()->find(PostsRequest::fromRun($runStep->run)->post);

        if (! $post) {
            throw new RuntimeException("Step [{$runStep->step_id}] has no post to write to.");
        }

        $version = $post->originalVersion();

        if (! $version) {
            throw new RuntimeException("Post [{$post->id}] has no original version to write to.");
        }

        $this->apply($version, $resolved)->save();

        $post->update(['run_id' => $runStep->run_id]);

        PostUpdated::dispatch($post->mixpostPost);
    }

    /**
     * The caption and title of the response, trimmed.
     *
     * @param  array<string, mixed>  $structured
     * @return array{caption: string, title: string|null}
     */
    public function resolve(AiRunStep $runStep, array $structured): array
    {
        $post = $structured[PostSchema::ROOT] ?? null;

        if (! is_array($post) || ! isset($post['caption']) || ! is_string($post['caption'])) {
            throw new RuntimeException("Step [{$runStep->step_id}] returned no post.");
        }

        $title = $post['title'] ?? null;

        return [
            'caption' => trim($post['caption']),
            'title' => is_string($title) && filled(trim($title)) ? trim($title) : null,
        ];
    }

    /**
     * Put the caption and title on a version without saving it.
     *
     * @param  array{caption: string, title: string|null}  $resolved
     */
    public function apply(PostVersion $version, array $resolved): PostVersion
    {
        $content = $version->content ?? [];

        if ($content === []) {
            $content = [['body' => '', 'media' => [], 'url' => null, 'video_thumbs' => []]];
        }

        $content[0]['body'] = MixpostCaption::toHtml($resolved['caption']);

        $version->content = array_values($content);
        $version->options = MixpostVersionOptions::withTitle($version->options ?? MixpostVersionOptions::defaults(), $resolved['title']);

        return $version;
    }
}
