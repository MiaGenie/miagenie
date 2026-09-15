<?php

namespace App\Abstracts;

use App\Concerns\GenieLogger;
use App\Contracts\GenieOutputContract;
use App\Contracts\GenieStateContract;
use App\Contracts\GenieSyncContract;
use App\Enums\GenieSyncAction;
use App\Models\File;
use App\Models\Vector;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\App;

abstract class GenieJob
{
    use GenieLogger;

    protected File|Vector $model;

    protected GenieSyncAction $action;

    public function __construct(
        File|Vector $model,
        GenieSyncAction $action,
    ) {
        $this->model = $model;
        $this->action = $action;
    }

    /**
     * @return GenieData|mixed|object
     *
     * @throws BindingResolutionException
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
     *
     * @throws BindingResolutionException
     */
    protected function getGenieAction(): mixed
    {
        return App::make(
            GenieSyncContract::class,
            [
                'model' => $this->model,
                'action' => $this->action,
            ]
        );
    }

    /**
     * @return GenieOutputContract
     *
     * @throws BindingResolutionException
     */
    protected function getGenieOutput(GenieData $data): mixed
    {
        return App::make(
            GenieOutputContract::class,
            [
                'model' => $this->model,
                'type' => $data->getType(),
            ]
        );
    }

    /**
     * @return GenieStateContract
     *
     * @throws BindingResolutionException
     */
    protected function getGenieState(GenieData $data): mixed
    {
        return App::make(
            GenieStateContract::class,
            [
                'type' => $data->getType(),
            ]
        );
    }
}
