<?php

namespace Tests\Unit;

use App\Genie\Posts\PostsOutput;
use App\Models\AiRunStep;
use Inovector\Mixpost\Models\PostVersion;
use RuntimeException;
use Tests\TestCase;

class PostsOutputTest extends TestCase
{
    protected function runStep(): AiRunStep
    {
        $runStep = new AiRunStep(['step_id' => 42]);
        $runStep->id = 99;

        return $runStep;
    }

    public function test_the_caption_and_title_are_trimmed_and_a_blank_title_is_null(): void
    {
        $output = new PostsOutput;

        $this->assertSame(
            ['caption' => 'Hello there', 'title' => 'Launch'],
            $output->resolve($this->runStep(), ['post' => ['caption' => "  Hello there\n", 'title' => ' Launch ']]),
        );

        $this->assertNull($output->resolve($this->runStep(), ['post' => ['caption' => 'Hi', 'title' => '  ']])['title']);
        $this->assertNull($output->resolve($this->runStep(), ['post' => ['caption' => 'Hi', 'title' => null]])['title']);
    }

    public function test_a_response_without_a_post_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('returned no post');

        (new PostsOutput)->resolve($this->runStep(), ['ideas' => []]);
    }

    public function test_apply_replaces_the_body_and_titles_and_keeps_everything_else(): void
    {
        $version = new PostVersion([
            'account_id' => 0,
            'is_original' => true,
            'content' => [
                ['body' => '<div>old</div>', 'media' => ['4', '5'], 'url' => 'https://x.test', 'video_thumbs' => [['media_id' => 4]]],
                ['body' => '<div>thread reply</div>', 'media' => [], 'url' => null, 'video_thumbs' => []],
            ],
            'options' => ['instagram' => ['type' => 'reel'], 'pinterest' => ['title' => 'Old', 'link' => 'l', 'boards' => ['account-0' => 3]]],
        ]);

        (new PostsOutput)->apply($version, ['caption' => "New line one\nline two", 'title' => 'Fresh']);

        $this->assertSame('<div>New line one</div><div>line two</div>', $version->content[0]['body']);
        $this->assertSame(['4', '5'], $version->content[0]['media']);
        $this->assertSame('https://x.test', $version->content[0]['url']);
        $this->assertSame([['media_id' => 4]], $version->content[0]['video_thumbs']);
        $this->assertSame('<div>thread reply</div>', $version->content[1]['body']);
        $this->assertSame('reel', $version->options['instagram']['type']);
        $this->assertSame('Fresh', $version->options['pinterest']['title']);
        $this->assertSame(3, $version->options['pinterest']['boards']['account-0']);
        $this->assertSame('Fresh', $version->options['youtube']['title']);
    }

    public function test_apply_creates_the_first_content_item_when_the_version_is_empty(): void
    {
        $version = new PostVersion(['content' => [], 'options' => null]);

        (new PostsOutput)->apply($version, ['caption' => 'Hi', 'title' => null]);

        $this->assertSame('<div>Hi</div>', $version->content[0]['body']);
        $this->assertSame([], $version->content[0]['media']);
        $this->assertNull($version->options['pinterest']['title']);
        $this->assertSame('post', $version->options['instagram']['type']);
    }
}
