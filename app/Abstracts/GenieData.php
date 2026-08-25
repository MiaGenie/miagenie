<?php

namespace App\Abstracts;

use App\Contracts\GenieDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Models\File;
use App\Models\RunResponse;
use App\Models\Vector;
use Illuminate\Support\Str;

abstract class GenieData implements GenieDataContract
{
    protected GenieSyncAction $action;

    protected File|Vector|RunResponse $model;

    protected array $data;

    protected array $request;

    protected array $response;

    protected bool $error;

    public int $duration;

    protected ?GenieSyncAction $nextAction;

    public function __construct(
        File|Vector|RunResponse $model,
        GenieSyncAction $action,
    ) {
        $this->model = $model;
        $this->action = $action;
        $this->data = [];
        $this->request = [];
        $this->response = [];
        $this->error = false;
        $this->duration = 0;
        $this->nextAction = null;
    }

    public function getAction(): GenieSyncAction
    {
        return $this->action;
    }

    public function getType(): GenieType
    {
        return GenieType::fromName(Str::snake(class_basename($this->model)));
    }

    public function getModel(): File|Vector|RunResponse
    {
        return $this->model;
    }

    public function getProviderIdField(): string
    {
        return mb_strtolower($this->getType()->title().'_provider_id');
    }

    public function getModelProviderId(): ?string
    {
        $providerIdField = mb_strtolower($this->getType()->title().'_provider_id');

        return $this->model->$providerIdField;
    }

    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function setResponseStatus(): void
    {
        if ($this->response['error'] ?? false) {
            $this->error = true;
        }
    }

    public function setError(bool $error): bool
    {
        return $this->error = $error;
    }

    public function getError(): bool
    {
        return $this->error;
    }

    public function setDuration(int $duration): int
    {
        return $this->duration = $duration;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getResponseProviderId(): ?string
    {
        return $this->response['id'] ?? null;
    }

    public function nextAction(): ?GenieSyncAction
    {
        return $this->nextAction;
    }
}
