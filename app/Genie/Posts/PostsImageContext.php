<?php

namespace App\Genie\Posts;

use App\Genie\Images\ImageContext;

/**
 * The placeholders of one image slot of a post: the slot's own (`{{{brief}}}`, `{{{feedback}}}`,
 * `{{{position}}}`) plus everything the posts context knows about the idea and the post, with
 * `{{{idea.IMAGE}}}` naming the brief the way the product note calls it, `{{{idea.IMAGE#}}}` the
 * slot number and `{{{idea.IMAGES#}}}` how many images the idea asks for.
 */
class PostsImageContext extends ImageContext
{
    public function __construct(
        protected PostsContext $posts,
        ?string $brief = null,
        ?string $feedback = null,
        int $position = 1,
    ) {
        parent::__construct($brief, $feedback, $position);
    }

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return array_merge(
            $this->posts->values(),
            parent::values(),
            ['idea.IMAGE' => $this->brief ?? '', 'idea.IMAGE#' => (string) $this->position],
        );
    }
}
