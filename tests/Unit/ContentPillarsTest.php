<?php

namespace Tests\Unit;

use App\Genie\Ideas\ContentPillars;
use App\Models\Strategy;
use Tests\TestCase;

class ContentPillarsTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $content
     */
    protected function strategy(array $content): Strategy
    {
        return new Strategy(['content' => $content]);
    }

    public function test_titles_are_read_from_the_name_key(): void
    {
        $strategy = $this->strategy([
            'content_pillars' => [
                ['name' => 'Craft', 'topics' => [], 'relative_importance' => 'High'],
                ['name' => 'Stories', 'topics' => [], 'relative_importance' => 'Low'],
            ],
        ]);

        $this->assertSame(['Craft', 'Stories'], ContentPillars::titles($strategy));
    }

    public function test_titles_are_read_from_the_legacy_title_key(): void
    {
        $strategy = $this->strategy([
            'content_pillars' => [
                ['title' => 'Craft', 'importance' => 'High'],
                ['title' => 'Stories', 'importance' => 'Low'],
            ],
        ]);

        $this->assertSame(['Craft', 'Stories'], ContentPillars::titles($strategy));
    }

    public function test_the_name_key_wins_over_the_title_key(): void
    {
        $this->assertSame('Current', ContentPillars::titleOf(['title' => 'Legacy', 'name' => 'Current']));
    }

    public function test_an_empty_name_falls_back_to_the_title(): void
    {
        $this->assertSame('Legacy', ContentPillars::titleOf(['name' => '', 'title' => 'Legacy']));
    }

    public function test_pillars_without_a_string_name_are_skipped(): void
    {
        $strategy = $this->strategy([
            'content_pillars' => [
                ['description' => 'No name at all'],
                ['name' => ['not', 'a', 'string']],
                ['name' => ''],
                'not a pillar',
                ['name' => 'Craft'],
            ],
        ]);

        $this->assertSame(['Craft'], ContentPillars::titles($strategy));
    }

    public function test_a_missing_or_malformed_field_has_no_pillars(): void
    {
        $this->assertSame([], ContentPillars::titles(null));
        $this->assertSame([], ContentPillars::titles($this->strategy([])));
        $this->assertSame([], ContentPillars::titles($this->strategy(['content_pillars' => 'Craft'])));
    }

    public function test_find_returns_the_pillar_with_that_name(): void
    {
        $stories = ['name' => 'Stories', 'description' => 'Who we made them for'];

        $strategy = $this->strategy([
            'content_pillars' => [
                ['name' => 'Craft', 'description' => 'How things are made'],
                $stories,
            ],
        ]);

        $this->assertSame($stories, ContentPillars::find($strategy, 'Stories'));
        $this->assertNull(ContentPillars::find($strategy, 'Gone'));
    }

    public function test_find_matches_a_legacy_titled_pillar(): void
    {
        $craft = ['title' => 'Craft', 'importance' => 'High'];

        $this->assertSame($craft, ContentPillars::find($this->strategy(['content_pillars' => [$craft]]), 'Craft'));
    }
}
