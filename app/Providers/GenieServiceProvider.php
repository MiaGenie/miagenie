<?php

namespace App\Providers;

use App\Abstracts\GenieData;
use App\Actions\CreateThread;
use App\Actions\GenieOutput;
use App\Actions\GenieOutput\ThreadOutput;
use App\Actions\GenieSync\CreateFile;
use App\Actions\GenieSync\DeleteFile;
use App\Actions\StatusThread;
use App\Actions\UpdateThread;
use App\Contracts\GenieOutputContract;
use App\Contracts\GenieSyncContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Genie\Data\Analysis;
use App\Genie\Data\GenieDataAssistants;
use App\Genie\Data\GenieDataFiles;
use App\Genie\Data\GenieDataVectors;
use Illuminate\Support\ServiceProvider;

class GenieServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(GenieSyncContract::class, function ($app, $params) {
            $model = $params['model'];
            $type = GenieType::fromName(class_basename($model));
            $action = $params['action'];

            switch ($type) {
                case GenieType::THREAD:
                    return match($action) {
                        GenieSyncAction::CREATE => new CreateThread(),
                        GenieSyncAction::UPDATE => new UpdateThread(),
                        GenieSyncAction::STATUS => new StatusThread(),
                    };
                case GenieType::FILE:
                    return match($action) {
                        GenieSyncAction::CREATE => new CreateFile(),
                        GenieSyncAction::DELETE => new DeleteFile()
                    };
            }
        });

        $this->app->bind(GenieData::class, function ($app, $params) {
            $model = $params['model'];
            $type = GenieType::fromName(class_basename($model));
            $action = $params['action'];

            switch ($type) {
                case GenieType::THREAD:
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

        });

        $this->app->bind(GenieOutputContract::class, function ($app, $params) {

            return match ($params['type']) {
                GenieType::FILE,
                GenieType::VECTOR,
                GenieType::ASSISTANT => new GenieOutput(),
                GenieType::THREAD => new ThreadOutput(),
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




