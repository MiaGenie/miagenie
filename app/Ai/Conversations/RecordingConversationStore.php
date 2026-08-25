<?php

namespace App\Ai\Conversations;

use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Storage\DatabaseConversationStore;

/**
 * The stock store generates a message id and returns it, but RememberConversation discards the
 * return value, and getLatestConversationMessages() maps rows into Message value objects that
 * carry role and content only — so a caller has no way to learn which transcript row a turn wrote.
 *
 * Remembering the last id written gives a run step an exact join to its message, instead of
 * inferring it from ordering.
 */
class RecordingConversationStore extends DatabaseConversationStore
{
    public ?string $lastAssistantMessageId = null;

    public function storeAssistantMessage(
        string $conversationId,
        ?string $participantType,
        string|int|null $participantId,
        AgentPrompt $prompt,
        AgentResponse $response,
    ): ?string {
        return $this->lastAssistantMessageId = parent::storeAssistantMessage(
            $conversationId,
            $participantType,
            $participantId,
            $prompt,
            $response,
        );
    }

    /**
     * Forget the previous turn, so a failed call cannot hand back a stale id.
     */
    public function forgetLastAssistantMessage(): void
    {
        $this->lastAssistantMessageId = null;
    }
}
