<?php

namespace App\Abstracts;

use App\Actions\GenieState;
use App\Concerns\GenieLogger;
use App\Contracts\GenieOutputContract;
use App\Contracts\GenieSyncContract;
use App\Enums\GenieSyncAction;
use Illuminate\Support\Facades\App;

abstract class GenieJob
{
    use GenieLogger;

    /**
     * @var \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Run|\App\Models\RunResponse
     */
    protected \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Run|\App\Models\RunResponse $model;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Run|\App\Models\RunResponse $model
     * @param GenieSyncAction $action
     */
    public function __construct(
        \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Run|\App\Models\RunResponse $model,
        GenieSyncAction $action,
    ) {
        $this->model = $model;
        $this->action = $action;
    }

    /**
     * @return GenieData|mixed|object
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieData(): mixed
    {
        return App::make(
            GenieData::class,
            [
                'model' => $this->model,
                'action' => $this->action
            ]
        );
    }

    /**
     * @return GenieSyncContract
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieAction(): mixed
    {
        return App::make(
            GenieSyncContract::class,
            [
                'model' => $this->model,
                'action' => $this->action
            ]
        );
    }

    /**
     * @return GenieOutputContract|mixed|object
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieOutput(GenieData $data): mixed
    {
        return App::make(
            GenieOutputContract::class,
            [
                'type' => $data->getType()
            ]
        );
    }

    /**
     * @return GenieState
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieState(): mixed
    {
        return App::make(GenieState::class);
    }
}
