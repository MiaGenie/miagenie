<?php

namespace App\Genie\Posts;

use App\Genie\Runs\RunPrompt;
use App\Models\AiRun;
use App\Models\GeniePost;
use App\Models\RuleStep;

/**
 * The message for one text step of a posts run: the briefing and the strategy every run can
 * read, plus the idea and the post as they stand — see PostsContext.
 */
class PostsPrompt extends RunPrompt
{
    public function __construct(
        AiRun $run,
        RuleStep $step,
        string $locale,
        PostsRequest $request,
        ?GeniePost $post = null,
    ) {
        parent::__construct($run, $step, $locale, new PostsContext($run, $step, $request, $locale, $post));
    }
}
