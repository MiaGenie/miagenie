<?php

namespace App\Providers;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Actions\GenieOutput\GenieOutputStrategy;
use App\Actions\GenieOutput\ThreadOutput;
use App\Actions\GenieRun\CreateResponse;
use App\Actions\GenieRun\RetrieveResponse;
use App\Actions\GenieSync\CreateFile;
use App\Actions\GenieSync\DeleteFile;
use App\Actions\GenieSync\DeleteVector;
use App\Actions\GenieSync\UploadVector;
use App\Contracts\GenieOutputContract;
use App\Contracts\GenieSyncContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Genie\Data\GenieDataAssistants;
use App\Genie\Data\GenieDataFiles;
use App\Genie\Data\GenieDataResponses;
use App\Genie\Data\GenieDataVectors;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class GenieServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(GenieSyncContract::class, function ($app, $params) {
            $model = $params['model'];
            $action = $params['action'];
            $modelName = Str::snake(class_basename($model));
            $type = GenieType::fromName($modelName);

            switch ($type) {
//                case GenieType::RUN:
//                    return match($action) {
//                        GenieSyncAction::CREATE => new CreateThread(),
//                        GenieSyncAction::UPDATE => new UpdateThread(),
//                        GenieSyncAction::STATUS => new StatusThread(),
//                    };
                case GenieType::RUN_RESPONSE:
                    return match($action) {
                        GenieSyncAction::CREATE => new CreateResponse(),
                        GenieSyncAction::RETRIEVE => new RetrieveResponse(),
                    };
                case GenieType::FILE:
                    return match($action) {
                        GenieSyncAction::CREATE => new CreateFile(),
                        GenieSyncAction::DELETE => new DeleteFile()
                    };
                case GenieType::VECTOR:
                    return match($action) {
                        GenieSyncAction::CREATE => new UploadVector(),
                        GenieSyncAction::DELETE => new DeleteVector()
                    };
            }
        });

        $this->app->bind(GenieData::class, function ($app, $params) {
            $model = $params['model'];
            $action = $params['action'];
            $modelName = Str::snake(class_basename($model));
            $type = GenieType::fromName($modelName);

            switch ($type) {
                case GenieType::RUN_RESPONSE:
                    return match($params['action']) {
                        GenieSyncAction::CREATE => new GenieDataResponses($model, $action),
                    };
                case GenieType::FILE:
                    return new GenieDataFiles($model, $action);
                case GenieType::VECTOR:
                    return new GenieDataVectors($model, $action);
                case GenieType::ASSISTANT:
                    return new GenieDataAssistants($model, $action);
            }

        });

        /*        $this->app->bind(GenieRunData::class, function ($app, $params) {
                    $model = $params['model'];
                    $type = GenieType::fromName(class_basename($model));
                    $action = $params['action'];

                    switch ($type) {
                        case GenieType::RUN:
                            return match($params['rule_type']) {
                                'ANALYSIS' => new Analysis($model, $action),
                            };
                        case GenieType::FILE:
                            return new GenieDataFiles($model, $action);
                        case GenieType::VECTOR:
                            return new GenieDataVectors($model, $action);
                        case GenieType::ASSISTANT:
                            return new GenieDataAssistants($model, $action);
                    }

                });*/

        $this->app->bind(GenieOutputContract::class, function ($app, $params) {

            return match ($params['type']) {
                GenieType::FILE,
                GenieType::VECTOR,
                GenieType::ASSISTANT => new GenieOutput(),
                GenieType::RUN => new ThreadOutput(),
                GenieType::RUN_RESPONSE => new GenieOutputStrategy(),
            };

        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

    }
}
