<?php

namespace Tests\Unit;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
use App\Genie\Ideas\IdeasRequest;
use App\Models\AiRun;
use Tests\TestCase;

class IdeasRequestTest extends TestCase
{
    public function test_an_empty_request_means_any_format_any_pillar_any_stage_and_the_default_count(): void
    {
        $request = IdeasRequest::fromArray([]);

        $this->assertTrue($request->wantsAnyFormat());
        $this->assertTrue($request->wantsAnyPillar());
        $this->assertTrue($request->wantsAnyStage());
        $this->assertSame(IdeasRequest::DEFAULT_COUNT, $request->count);
    }

    public function test_it_reads_what_the_page_sent(): void
    {
        $request = IdeasRequest::fromArray([
            'post_format' => '2',
            'content_pillar' => 'Client Success Stories',
            'funnel_stage' => 4,
            'count' => '20',
        ]);

        $this->assertSame(PostFormat::CAROUSEL, $request->postFormat);
        $this->assertSame('Client Success Stories', $request->contentPillar);
        $this->assertSame(FunnelStage::CONSIDERATION, $request->funnelStage);
        $this->assertSame(20, $request->count);
    }

    public function test_it_round_trips_through_the_run_parameters(): void
    {
        $request = new IdeasRequest(PostFormat::SHORT_VIDEO, 'Tech and Tools', FunnelStage::AWARENESS, 5);

        $run = new AiRun(['parameters' => $request->toArray()]);

        $read = IdeasRequest::fromRun($run);

        $this->assertSame(PostFormat::SHORT_VIDEO, $read->postFormat);
        $this->assertSame('Tech and Tools', $read->contentPillar);
        $this->assertSame(FunnelStage::AWARENESS, $read->funnelStage);
        $this->assertSame(5, $read->count);
    }

    public function test_a_blank_pillar_is_any_pillar(): void
    {
        $this->assertTrue(IdeasRequest::fromArray(['content_pillar' => ''])->wantsAnyPillar());
    }

    public function test_the_total_is_split_exactly_with_the_remainder_on_the_first_steps(): void
    {
        $this->assertSame([2, 1, 1, 1], IdeasRequest::fromArray(['count' => 5])->shares(4));
        $this->assertSame([4, 3, 3], IdeasRequest::fromArray(['count' => 10])->shares(3));
        $this->assertSame([5, 5, 5, 5], IdeasRequest::fromArray(['count' => 20])->shares(4));
        $this->assertSame([10], IdeasRequest::fromArray(['count' => 10])->shares(1));
    }

    public function test_no_generators_means_no_shares(): void
    {
        $this->assertSame([], IdeasRequest::fromArray(['count' => 10])->shares(0));
        $this->assertSame(0, IdeasRequest::fromArray(['count' => 10])->shareFor(0, 0));
    }

    public function test_a_step_reads_its_own_share_by_position(): void
    {
        $request = IdeasRequest::fromArray(['count' => 5]);

        $this->assertSame(2, $request->shareFor(0, 4));
        $this->assertSame(1, $request->shareFor(3, 4));
        $this->assertSame(0, $request->shareFor(4, 4));
    }
}
