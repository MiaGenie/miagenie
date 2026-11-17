<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ModelTier;
use App\Genie\Images\ImageContext;
use App\Genie\Images\ImageGenerator;
use App\Genie\Images\ImageOptions;
use App\Genie\Images\ImagePrompt;
use App\Models\AiRun;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use App\Models\WorkspaceFile;
use Illuminate\Support\Collection;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\StoredImage;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt as SdkImagePrompt;
use Laravel\Ai\Responses\Data\ImageUsage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\ImageResponse;
use RuntimeException;
use Tests\TestCase;

class ImageGeneratorTest extends TestCase
{
    /**
     * A factory that never touches the database for credentials and answers a fixed model.
     */
    protected function agents(?string $model = null, int $timeout = 120): StepAgentFactory
    {
        $agents = $this->createMock(StepAgentFactory::class);

        $agents->method('prepare')->willReturn(Lab::OpenAI);
        $agents->method('timeout')->willReturn($timeout);
        $agents->method('imageModel')->willReturn($model);

        return $agents;
    }

    protected function prompt(?WorkspaceFile $reference = null, string $feedback = ''): ImagePrompt
    {
        $step = new RuleStep(['name' => 'Hero', 'message' => ['en-GB' => 'Draw {{{brief}}} {{{feedback}}}']]);
        $step->setRelation('modelProfile', new ModelProfile(['provider' => 'openai', 'model_tier' => ModelTier::DEFAULT]));

        return new ImagePrompt(new AiRun, $step, 'en-GB', new ImageContext('a red chair', $feedback ?: null), $reference);
    }

    public function test_the_rendered_text_and_options_reach_the_provider_without_attachments(): void
    {
        Image::fake();

        $response = (new ImageGenerator($this->agents()))->generate($this->prompt(), new ImageOptions('1:1', 'low'));

        $this->assertCount(1, $response);
        $this->assertSame('image/png', $response->firstImage()->mime());

        Image::assertGenerated(fn (SdkImagePrompt $prompt) => $prompt->prompt === 'Draw a red chair '
            && $prompt->isSquare()
            && $prompt->quality === 'low'
            && $prompt->attachments->isEmpty()
            && $prompt->model === 'gpt-image-2');
    }

    public function test_a_reference_file_is_attached_from_its_disk(): void
    {
        Image::fake();

        $reference = new WorkspaceFile(['path' => 'workspace/w/generated/a.png', 'disk' => 'public']);

        (new ImageGenerator($this->agents()))->generate($this->prompt($reference, 'bluer'));

        Image::assertGenerated(function (SdkImagePrompt $prompt) {
            $attachment = $prompt->attachments->first();

            return $attachment instanceof StoredImage
                && $attachment->path === 'workspace/w/generated/a.png'
                && $attachment->disk === 'public'
                && $prompt->contains('bluer');
        });
    }

    public function test_an_explicit_model_overrides_the_provider_default(): void
    {
        Image::fake();

        (new ImageGenerator($this->agents('gpt-image-1')))->generate($this->prompt());

        Image::assertGenerated(fn (SdkImagePrompt $prompt) => $prompt->model === 'gpt-image-1');
    }

    public function test_a_response_without_images_is_refused(): void
    {
        Image::fake([new ImageResponse(new Collection, new ImageUsage, new Meta('openai', 'gpt-image-2'))]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('returned no image');

        (new ImageGenerator($this->agents()))->generate($this->prompt());
    }
}
