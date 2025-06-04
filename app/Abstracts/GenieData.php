<?php

namespace App\Abstracts;

use App\Contracts\GenieDataContract;

abstract class GenieData implements GenieDataContract
{
    /**
     * @var string
     */
    protected string $type;

    /**
     * @var string
     */
    protected string $action;

    /**
     * @var \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Thread
     */
    protected \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Thread $model;

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
     * @var string
     */
    protected string $nextAction;

    /**
     * @param string $type
     * @param string $action
     */
    public function __construct(
        string $type,
        string $action,
        \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Thread $model
    ) {
        $this->type = $type;
        $this->action = $action;
        $this->model = $model;
        $this->data = [];
        $this->request = [];
        $this->response = [];
        $this->nextAction = '';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
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

    public function nextAction(): string
    {
        return $this->nextAction;
    }

}
