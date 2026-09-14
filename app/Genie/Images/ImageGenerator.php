<?php

namespace App\Genie\Images;

use App\Ai\StepAgentFactory;
use Laravel\Ai\Files\Image as ImageFile;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;
use RuntimeException;

/**
 * Sends one image prompt to the step's provider.
 *
 * Nothing is persisted here: the caller decides where the bytes go (ImageStore). The provider and
 * its credentials come from the step's model profile exactly as they do for a text step; the model
 * is the profile's explicit one or the provider's single image model.
 */
class ImageGenerator
{
    public function __construct(protected StepAgentFactory $agents = new StepAgentFactory) {}

    public function generate(ImagePrompt $prompt, ImageOptions $options = new ImageOptions): ImageResponse
    {
        $step = $prompt->step();

        $provider = $this->agents->prepare($step);

        $generation = Image::of($prompt->text())->timeout($this->agents->timeout($step));

        if ($reference = $prompt->reference()) {
            $generation->attachments([ImageFile::fromStorage($reference->path, $reference->disk)]);
        }

        $response = $options->apply($generation)->generate($provider, $this->agents->imageModel($step));

        if ($response->count() === 0) {
            throw new RuntimeException("Step [{$step->id}] returned no image.");
        }

        return $response;
    }
}
