<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Concerns\CleanAsterisks;
use App\Contracts\GenieOutputContract;
use App\Enums\DraftStatus;
use App\Enums\PrePostStatus;
use App\Enums\RunResponseError;
use App\Enums\RunResponseStatus;
use App\Models\PrePost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Models\Post;

class GenieOutputPrePosts extends GenieOutput implements GenieOutputContract
{
    use CleanAsterisks;

    /**
     * @param GenieData $data
     */
    public function handle(GenieData $data): void
    {
        try {
            parent::handle($data);
            /** @var \App\Models\RunResponse  $model */
            $model = $data->getModel();
            $response = $data->getResponse();

            $model->update([
                'provider_status' => RunResponseStatus::fromName($response['status']),
                'output' => $response['output'],
                'output_text' => $response['output_text'],
                'error' => $response['error'] ? RunResponseError::fromName($response['error']['code']) : null,
                'error_details' => $response['error'] ? $response['error']['message'] : null,
                'incomplete_details' => $response['incomplete_details'] ? $response['incomplete_details']['reason'] : null,
            ]);

            if (empty($model->step->output)) {
                return;
            }

            $model->runDraftResponse->runDraft->draft->update(['status' => DraftStatus::PUBLISHED]);

            $prePostData = [
                'draft_id' => $model->runDraftResponse->runDraft->draft_id,
                'status' => PrePostStatus::CREATED,
            ];
            $responseOutput = $response['output'][0]['content'][0]['text'];
            $responseOutput = $this->cleanAsterisks($responseOutput);
            $output = json_decode($responseOutput, true);
            foreach ($model->step->output as $stepOutput) {
                $prePostData[$stepOutput] = $output[$stepOutput] ?? '';
            }

            $prePost = PrePost::create($prePostData);

            $prePost->prePostRunResponses()->create([
                'run_response_id' => $model->id,
            ]);

            $post = DB::transaction(function () use ($prePost, $model) {

                $post = Post::create([
                    'user_id' => $model->run->workspace->owner_id,
                    'status' => PostStatus::DRAFT,
                    'scheduled_at' => null,
                ]);

                $post->versions()->create([
                    'account_id' => 0,
                    'is_original' => 0,
                    'content' => [[
                        'body' => $this->lineToDiv($prePost->caption ?? ''),
                        'media' => [],
                        'url' => null,
                    ]],
                    'options' => []
                ]);

                return $post;
            });

            $prePost->update(['post_id' =>  $post->id]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * @param string $text
     * @return string
     */
    private function lineToDiv(string $text): string
    {
        $output = "<div>";
        $output .= str_replace("\n", "</div><div>", $text);
        $output .= "</div>";
        return $output;
    }

}
