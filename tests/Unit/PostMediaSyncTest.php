<?php

namespace Tests\Unit;

use App\Genie\Posts\PostMediaSync;
use App\Models\GeniePost;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\PostVersion;
use Tests\TestCase;

class PostMediaSyncTest extends TestCase
{
    public function test_placing_appends_missing_ids_after_what_is_there(): void
    {
        $this->assertSame(['9', '1', '2'], PostMediaSync::placeIds([9], ['1', '2']));
        $this->assertSame(['1', '2'], PostMediaSync::placeIds(['1'], ['1', '2']));
        $this->assertSame([], PostMediaSync::placeIds([], []));
    }

    public function test_swapping_keeps_the_index_of_the_old_id(): void
    {
        $this->assertSame(['7', '20', '9'], PostMediaSync::swapIds(['7', '2', '9'], '2', '20'));
    }

    public function test_a_missing_old_id_goes_after_the_nearest_lower_slot_still_present(): void
    {
        // Slot 3 was removed; slot 2 (id 2) is still in the post, slot 1 (id 1) too. Nearest first.
        $this->assertSame(['7', '1', '2', '30', '9'], PostMediaSync::swapIds(['7', '1', '2', '9'], '3', '30', ['2', '1']));

        // Slot 2 is gone as well: fall back to slot 1.
        $this->assertSame(['7', '1', '30', '9'], PostMediaSync::swapIds(['7', '1', '9'], '3', '30', ['2', '1']));

        // Nothing to anchor on: append.
        $this->assertSame(['7', '9', '30'], PostMediaSync::swapIds(['7', '9'], '3', '30', ['2', '1']));
    }

    public function test_slot_state_is_derived_from_the_version_and_the_media_rows(): void
    {
        $version = new PostVersion([
            'account_id' => 0,
            'is_original' => true,
            'content' => [['body' => '', 'media' => [11, 99], 'url' => null, 'video_thumbs' => []]],
        ]);

        $mixpost = new Post;
        $mixpost->setRelation('versions', new Collection([$version]));

        $post = new GeniePost(['images' => [
            ['position' => 2, 'file_id' => 5, 'media_id' => '12'],
            ['position' => 1, 'file_id' => 4, 'media_id' => '11'],
            ['position' => 3, 'file_id' => 6, 'media_id' => '13'],
        ]]);
        $post->setRelation('mixpostPost', $mixpost);

        $slots = $post->slots(['11', '12']);

        $this->assertSame([1, 2, 3], array_column($slots, 'position'));
        $this->assertSame([true, false, false], array_column($slots, 'in_post'));
        $this->assertSame([true, true, false], array_column($slots, 'media_exists'));
        $this->assertSame(['11', '12', '13'], $post->mediaIds());
        $this->assertSame(['11', '99'], $post->versionMediaIds());
    }
}
