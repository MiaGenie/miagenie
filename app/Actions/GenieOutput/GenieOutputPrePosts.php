<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Concerns\CleanAsterisks;
use App\Concerns\PersistsStepResponse;
use App\Contracts\GenieOutputContract;
use App\Enums\DraftStatus;
use App\Enums\PrePostStatus;
use App\Models\PrePost;
use App\Models\RunResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Models\Post;

class GenieOutputPrePosts extends GenieOutput implements GenieOutputContract
{
    use CleanAsterisks;
    use PersistsStepResponse;

    public function handle(GenieData $data): void
    {
        try {
            parent::handle($data);
            /** @var RunResponse $model */
            $model = $data->getModel();
            $response = $data->getResponse();

            $this->persistResponse($model, $response);

            if (empty($model->step->output)) {
                return;
            }

            $model->runDraftResponse->runDraft->draft->update(['status' => DraftStatus::PUBLISHED]);

            $prePostData = [
                'draft_id' => $model->runDraftResponse->runDraft->draft_id,
                'status' => PrePostStatus::CREATED,
            ];
            $output = $this->structuredOutput($response) ?? [];
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
                    'is_original' => 1,
                    'content' => [[
                        'body' => $this->lineToDiv($prePost->caption ?? ''),
                        'media' => [],
                        'url' => null,
                    ]],
                    'options' => $this->versionOptions(),
                ]);

                return $post;
            });

            $prePost->update(['post_id' => $post->id]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    private function lineToDiv(string $text): string
    {
        $output = '<div>';
        $output .= str_replace("\n", '</div><div>', $text);
        $output .= '</div>';

        return $output;
    }

    private function versionOptions(): array
    {
        return [
            'tiktok' => [
                'allow_duet' => [
                    'account-0' => false,
                ],
                'allow_stitch' => [
                    'account-0' => false,
                ],
                'privacy_level' => [
                    'account-0' => null,
                ],
                'allow_comments' => [
                    'account-0' => false,
                ],
                'content_disclosure' => [
                    'account-0' => false,
                ],
                'brand_content_toggle' => [
                    'account-0' => false,
                ],
                'brand_organic_toggle' => [
                    'account-0' => false,
                ],
            ],
            'youtube' => [
                'title' => null,
                'status' => 'public',
            ],
            'linkedin' => [
                'visibility' => 'PUBLIC',
            ],
            'mastodon' => [
                'sensitive' => false,
            ],
            'instagram' => [
                'type' => 'post',
            ],
            'pinterest' => [
                'link' => null,
                'title' => null,
                'boards' => [
                    'account-0' => null,
                ],
            ],
            'facebook_page' => [
                'type' => 'post',
            ],
        ];
    }
}
