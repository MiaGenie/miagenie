<?php

namespace App\Genie\Posts;

use App\Enums\GeniePostStatus;
use App\Genie\Runs\RunState;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\GeniePost;

/**
 * Keeps Genie's record of the post in step with its run: generating while it runs, ready when
 * every step has written, failed with the message when one could not.
 */
class PostsRunState extends RunState
{
    public function failed(AiRunStep $runStep, ?string $details = null): void
    {
        parent::failed($runStep, $details);

        $this->postOf($runStep->run)?->update([
            'status' => GeniePostStatus::FAILED,
            'error' => $details ? mb_substr($details, 0, 255) : null,
        ]);
    }

    protected function onStarting(AiRun $run): void
    {
        $this->postOf($run)?->update(['status' => GeniePostStatus::GENERATING, 'error' => null]);
    }

    protected function onFinished(AiRun $run): void
    {
        $this->postOf($run)?->update(['status' => GeniePostStatus::READY, 'error' => null, 'run_id' => $run->id]);
    }

    protected function postOf(AiRun $run): ?GeniePost
    {
        $id = PostsRequest::fromRun($run)->post;

        return $id > 0 ? GeniePost::withoutWorkspace()->find($id) : null;
    }
}
