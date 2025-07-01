<?php

namespace App\Abstracts;

use App\Contracts\GenieDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use Illuminate\Support\Str;

abstract class GenieData implements GenieDataContract
{

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @var \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\RunResponse
     */
    protected \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\RunResponse $model;

    /**
     * @var array
     */
    protected array $data;

    /**
     * @var array
     */
    protected array $request;

    /**
     * @var array
     */
    protected array $response;

    /**
     * @var int
     */
    public int $duration;

    /**
     * @var ?GenieSyncAction
     */
    protected ?GenieSyncAction $nextAction;

    /**
     * @param \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\RunResponse $model,
     * @param GenieSyncAction $action
     */
    public function __construct(
        \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\RunResponse $model,
        GenieSyncAction $action,
    ) {
        $this->model = $model;
        $this->action = $action;
        $this->data = [];
        $this->request = [];
        $this->response = [];
        $this->duration = 0;
        $this->nextAction = null;
    }

    /**
     * @return GenieSyncAction
     */
    public function getAction(): GenieSyncAction
    {
        return $this->action;
    }

    /**
     * @return GenieType
     */
    public function getType(): GenieType
    {
        return GenieType::fromName(Str::snake(class_basename($this->model)));
    }

    /**
     * @return \App\Models\File | \App\Models\Vector | \App\Models\Assistant | \App\Models\Run | \App\Models\RunResponse
     */
    public function getModel(): \App\Models\File | \App\Models\Vector | \App\Models\Assistant | \App\Models\Run | \App\Models\RunResponse
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getProviderIdField(): string
    {
        return mb_strtolower($this->getType()->title() . '_provider_id');
    }

    /**
     * @return ?string
     */
    public function getModelProviderId(): ?string
    {
        $providerIdField = mb_strtolower($this->getType()->title() . '_provider_id');
        return $this->model->$providerIdField;
    }

    /**
     * @param array $response
     * @return void
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @param int $duration
     * @return int
     */
    public function setDuration(int $duration): int
    {
        return $this->duration = $duration;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @return string
     */
    public function getResponseProviderId(): string
    {
        return $this->response['id'];
    }

    /**
     * @return ?GenieSyncAction
     */
    public function nextAction(): ?GenieSyncAction
    {
        return $this->nextAction;
    }

}
