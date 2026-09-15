<?php

namespace Tests\Unit;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
use App\Genie\Images\ImageContext;
use App\Genie\Posts\PostsContext;
use App\Genie\Posts\PostsImageContext;
use App\Genie\Posts\PostsRequest;
use App\Models\AiRun;
use App\Models\GeniePost;
use App\Models\Idea;
use App\Models\RuleStep;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\PostVersion;
use Tests\TestCase;

class PostsContextTest extends TestCase
{
    protected function geniePost(array $idea = [], ?string $body = '<div>Current caption</div><div>second line</div>', ?string $title = 'Current title'): GeniePost
    {
        $version = new PostVersion([
            'account_id' => 0,
            'is_original' => true,
            'content' => [['body' => $body, 'media' => [], 'url' => null, 'video_thumbs' => []]],
            'options' => ['pinterest' => ['title' => $title]],
        ]);

        $mixpost = new Post;
        $mixpost->setRelation('versions', new Collection([$version]));

        $post = new GeniePost(['post_format' => PostFormat::CAROUSEL]);
        $post->setRelation('mixpostPost', $mixpost);
        $post->setRelation('idea', new Idea(array_merge([
            'theme' => 'Launch day',
            'objective' => 'Announce the launch',
            'suggested_content' => 'Three things to know',
            'multimedia_suggestion' => 'Product shots',
            'production_instructions' => 'Bright, clean',
            'funnel_stage' => FunnelStage::AWARENESS,
            'content_pillar' => 'Craft',
            'post_format' => PostFormat::CAROUSEL,
            'publish_to' => ['instagram', 'linkedin_page'],
            'images' => ['Cover: the box', 'Slide: the product'],
        ], $idea)));

        return $post;
    }

    protected function context(GeniePost $post, ?string $feedback = null, ?ImageContext $image = null): PostsContext
    {
        return new PostsContext(
            new AiRun,
            new RuleStep(['name' => 'Caption']),
            new PostsRequest(1, 2, PostFormat::CAROUSEL, null, $feedback),
            'en-GB',
            $post,
            $image,
        );
    }

    public function test_every_idea_field_and_the_post_are_exposed(): void
    {
        $values = $this->context($this->geniePost(), 'Shorter please')->values();

        $this->assertSame('Launch day', $values['idea.theme']);
        $this->assertSame('Announce the launch', $values['idea.objective']);
        $this->assertSame('Three things to know', $values['idea.suggested_content']);
        $this->assertSame('Product shots', $values['idea.multimedia_suggestion']);
        $this->assertSame('Bright, clean', $values['idea.production_instructions']);
        $this->assertSame(__('genie.funnel_stage_awareness', [], 'en-GB'), $values['idea.funnel_stage']);
        $this->assertSame('Craft', $values['idea.content_pillar']);
        $this->assertSame(__('genie.post_format_carousel', [], 'en-GB'), $values['idea.post_format']);
        $this->assertSame('Instagram, LinkedIn', $values['idea.publish_to']);
        $this->assertSame("1. Cover: the box\n2. Slide: the product", $values['idea.images']);
        $this->assertSame("Current caption\nsecond line", $values['post.caption']);
        $this->assertSame('Current title', $values['post.title']);
        $this->assertSame('Shorter please', $values['feedback']);
        $this->assertSame('', $values['idea.IMAGE']);
        $this->assertSame('', $values['idea.IMAGE#']);
        $this->assertSame('2', $values['idea.IMAGES#']);
    }

    public function test_an_idea_without_briefs_counts_zero_images(): void
    {
        $values = $this->context($this->geniePost(['images' => null]))->values();

        $this->assertSame('0', $values['idea.IMAGES#']);
        $this->assertSame('', $values['idea.images']);
    }

    public function test_full_renders_labelled_blocks_and_skips_empty_fields(): void
    {
        $full = $this->context($this->geniePost(['multimedia_suggestion' => null, 'content_pillar' => null]))->full();

        $this->assertStringContainsString(__('genie.idea_title', [], 'en-GB').': Launch day', $full);
        $this->assertStringContainsString(__('genie.idea_objective', [], 'en-GB').': Announce the launch', $full);
        $this->assertStringContainsString(__('genie.idea_images', [], 'en-GB').': 1. Cover: the box', $full);
        $this->assertStringNotContainsString(__('genie.idea_multimedia_suggestion', [], 'en-GB').':', $full);
        $this->assertStringNotContainsString(__('genie.content_pillar', [], 'en-GB').':', $full);
    }

    public function test_an_empty_post_and_no_feedback_render_as_empty_strings(): void
    {
        $values = $this->context($this->geniePost([], '', null))->values();

        $this->assertSame('', $values['post.caption']);
        $this->assertSame('', $values['post.title']);
        $this->assertSame('', $values['feedback']);
    }

    public function test_the_image_context_adds_the_slot_and_names_the_brief_as_idea_image(): void
    {
        $post = $this->geniePost();

        $image = new PostsImageContext($this->context($post), 'Slide: the product', 'More contrast', 2);

        $values = $image->values();

        $this->assertSame('Slide: the product', $values['idea.IMAGE']);
        $this->assertSame('2', $values['idea.IMAGE#']);
        $this->assertSame('2', $values['idea.IMAGES#']);
        $this->assertSame('Slide: the product', $values['brief']);
        $this->assertSame('More contrast', $values['feedback']);
        $this->assertSame('2', $values['position']);
        $this->assertSame('Launch day', $values['idea.theme']);
        $this->assertSame("Current caption\nsecond line", $values['post.caption']);
        $this->assertTrue($image->hasFeedback());
    }
}
