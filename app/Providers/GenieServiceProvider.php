<?php

namespace App\Providers;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Actions\GenieState\GenieStateSyncs;
use App\Actions\GenieSync\CreateFile;
use App\Actions\GenieSync\DeleteFile;
use App\Actions\GenieSync\DeleteVector;
use App\Actions\GenieSync\UploadVector;
use App\Contracts\GenieOutputContract;
use App\Contracts\GenieStateContract;
use App\Contracts\GenieSyncContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Genie\Data\GenieDataFiles;
use App\Genie\Data\GenieDataVectors;
use App\Models\Workspace;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inovector\Mixpost\Mixpost;

/**
 * Bindings for the provider file / vector sync. Runs are driven by `App\Genie\Runs` and `AiRunJob`.
 */
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
                case GenieType::FILE:
                    return match ($action) {
                        GenieSyncAction::CREATE => new CreateFile,
                        GenieSyncAction::DELETE => new DeleteFile
                    };
                case GenieType::VECTOR:
                    return match ($action) {
                        GenieSyncAction::CREATE => new UploadVector,
                        GenieSyncAction::DELETE => new DeleteVector
                    };
            }
        });

        $this->app->bind(GenieData::class, function ($app, $params) {
            $model = $params['model'];
            $action = $params['action'];
            $modelName = Str::snake(class_basename($model));
            $type = GenieType::fromName($modelName);

            switch ($type) {
                case GenieType::FILE:
                    return new GenieDataFiles($model, $action);
                case GenieType::VECTOR:
                    return new GenieDataVectors($model, $action);
            }

        });

        $this->app->bind(GenieOutputContract::class, function ($app, $params) {
            return match ($params['type']) {
                GenieType::FILE,
                GenieType::VECTOR => new GenieOutput,
            };
        });

        $this->app->bind(GenieStateContract::class, function ($app, $params) {
            return match ($params['type']) {
                GenieType::FILE,
                GenieType::VECTOR => new GenieStateSyncs,
            };
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mixpost::customWorkspaceModel(Workspace::class);
    }
}
