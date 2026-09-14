<?php

namespace Tests\Unit;

use App\Enums\PostFormat;
use App\Genie\Posts\PostsRequest;
use App\Genie\Runs\PostFormatGate;
use App\Models\AiRun;
use Tests\TestCase;

class PostsRequestTest extends TestCase
{
    public function test_a_first_generation_wants_text_and_images(): void
    {
        $request = new PostsRequest(3, 7, PostFormat::CAROUSEL);

        $this->assertTrue($request->wantsText());
        $this->assertTrue($request->wantsImages());
        $this->assertFalse($request->isRegeneration());
        $this->assertFalse($request->hasFeedback());
    }

    public function test_the_format_is_stored_under_the_gate_key_and_round_trips(): void
    {
        $request = new PostsRequest(3, 7, PostFormat::SIMPLE_POST, PostsRequest::ONLY_TEXT, 'Shorter');

        $parameters = $request->toArray();

        $this->assertSame(PostFormat::SIMPLE_POST->value, $parameters[PostFormatGate::PARAMETER]);

        $read = PostsRequest::fromRun(new AiRun(['parameters' => $parameters]));

        $this->assertSame(3, $read->idea);
        $this->assertSame(7, $read->post);
        $this->assertSame(PostFormat::SIMPLE_POST, $read->postFormat);
        $this->assertSame('text', $read->only);
        $this->assertSame('Shorter', $read->feedback);
        $this->assertTrue($read->isRegeneration());
        $this->assertTrue($read->wantsText());
        $this->assertFalse($read->wantsImages());
    }

    public function test_only_images_skips_the_text(): void
    {
        $request = PostsRequest::fromArray(['idea' => 1, 'post' => 2, 'only' => 'images']);

        $this->assertFalse($request->wantsText());
        $this->assertTrue($request->wantsImages());
        $this->assertTrue($request->isRegeneration());
    }

    public function test_malformed_parameters_fall_back_to_defaults(): void
    {
        $request = PostsRequest::fromArray(['only' => 'everything', 'feedback' => '   ', 'post_format' => 'x']);

        $this->assertSame(0, $request->idea);
        $this->assertSame(0, $request->post);
        $this->assertNull($request->postFormat);
        $this->assertNull($request->only);
        $this->assertNull($request->feedback);
    }
}
