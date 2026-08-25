<?php

namespace App\Ai\Agents;

use App\Models\ModelProfile;
use App\Models\RuleStep;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * A rule step expressed as an AI SDK agent.
 *
 * Steps are database rows, so the SDK's static #[Model] and #[UseCheapestModel] attributes do
 * not fit: the model is chosen by the step's profile at run time, which is what model() and
 * getDefaultModelFor() below express. Everything else — temperature, top_p, reasoning — is left
 * to the provider default, so there is nothing else to map.
 *
 * Conversational + RemembersConversations is what carries a run's earlier steps into the prompt.
 * GeneratesText::gatherMiddlewareFor() looks for the trait with class_uses_recursive() and appends
 * the RememberConversation middleware itself, so no registration is needed; the caller only has to
 * say which conversation the step belongs to.
 */
class StepAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    /**
     * How many earlier messages of the run to replay.
     *
     * The SDK default is 100. A strategy run is 14 turns (28 messages), so this only bites on a
     * long resumed run, where the oldest context is the least useful.
     */
    protected function maxConversationMessages(): int
    {
        return 60;
    }

    public function __construct(
        public RuleStep $step,
        public string $locale,
    ) {}

    public function instructions(): Stringable|string
    {
        return (string) $this->step->getTranslation('instructions', $this->locale);
    }

    public function profile(): ?ModelProfile
    {
        return $this->step->modelProfile;
    }

    /**
     * The model the profile names outright.
     *
     * Promptable only consults this when the caller passes no model, and a null answer leaves
     * the choice to getDefaultModelFor() below — which is what a tiered profile wants.
     */
    public function model(): ?string
    {
        return $this->profile()?->explicitModel();
    }

    /**
     * Resolve the profile's tier against the provider that is about to be prompted.
     *
     * The SDK reads its #[UseCheapestModel] / #[UseSmartestModel] attributes here, which are
     * fixed per class and so cannot express a profile held in the database. Overriding the
     * method reaches the same provider accessors, and therefore the same config/ai.php models.
     */
    protected function getDefaultModelFor(TextProvider $provider): string
    {
        return $this->profile()?->model_tier?->textModelFor($provider)
            ?? $provider->defaultTextModel();
    }
}
