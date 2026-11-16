<?php

namespace App\Listeners;

use App\Enums\Modality;
use App\Models\TokenUsage;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\ImageGenerated;
use Laravel\Ai\Responses\Data\Usage;
use Throwable;

/**
 * Counts the tokens of every model call made for a workspace.
 *
 * The SDK dispatches these events synchronously inside the call, so the workspace is the one the
 * job (AiRunJob, RevisePostImageJob) or the request made current. Calls made with no current
 * workspace, such as admin translations, are not anyone's usage and are skipped.
 */
class RecordTokenUsage
{
    public function handleAgentPrompted(AgentPrompted $event): void
    {
        $this->record(
            Modality::TEXT,
            $event->response->usage,
            $event->response->meta->provider,
            $event->response->meta->model,
        );
    }

    public function handleImageGenerated(ImageGenerated $event): void
    {
        $this->record(
            Modality::IMAGE,
            $event->response->usage,
            $event->provider->name(),
            $event->model,
        );
    }

    /**
     * Failures are reported, never thrown: the call has already been paid for, and an exception
     * here would fail the step and make its job pay for it again.
     */
    protected function record(Modality $modality, Usage $usage, ?string $provider, ?string $model): void
    {
        $workspace = WorkspaceManager::current();

        if (! $workspace) {
            return;
        }

        try {
            TokenUsage::create([
                'workspace_id' => $workspace->id,
                'modality' => $modality,
                'provider' => $provider,
                'model' => $model,
                ...TokenUsage::countsFrom($usage, $provider),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
