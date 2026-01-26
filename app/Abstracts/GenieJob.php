<?php

namespace App\Abstracts;

use App\Actions\GenieState\GenieStateRuns;
use App\Actions\GenieState\GenieStateStrategies;
use App\Actions\GenieState\GenieStateSyncs;
use App\Concerns\GenieLogger;
use App\Contracts\GenieOutputContract;
use App\Contracts\GenieStateContract;
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
                'action' => $this->action,
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
     * @return GenieOutputContract
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieOutput(GenieData $data): mixed
    {
        return App::make(
            GenieOutputContract::class,
            [
                'model' => $this->model,
                'type' => $data->getType()
            ]
        );
    }

    /**
     * @return GenieStateContract
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieState(GenieData $data): mixed
    {
        return App::make(
            GenieStateContract::class,
            [
                'type' => $data->getType()
            ]
        );
    }

    /**
     * @return GenieStateRuns
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieStateRuns(): mixed
    {
        return App::make(GenieStateRuns::class);
    }

    /**
     * @return GenieStateStrategies
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getGenieStateStrategy(): mixed
    {
        return App::make(GenieStateStrategies::class);
    }
}
