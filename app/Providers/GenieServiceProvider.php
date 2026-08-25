<?php

namespace App\Providers;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Actions\GenieOutput\GenieOutputDrafts;
use App\Actions\GenieOutput\GenieOutputIdeas;
use App\Actions\GenieOutput\GenieOutputPrePosts;
use App\Actions\GenieOutput\GenieOutputStrategy;
use App\Actions\GenieRun\CreateResponse;
use App\Actions\GenieState\GenieStateRunResponses;
use App\Actions\GenieState\GenieStateSyncs;
use App\Actions\GenieSync\CreateFile;
use App\Actions\GenieSync\DeleteFile;
use App\Actions\GenieSync\DeleteVector;
use App\Actions\GenieSync\UploadVector;
use App\Contracts\GenieOutputContract;
use App\Contracts\GenieRunDataContract;
use App\Contracts\GenieStateContract;
use App\Contracts\GenieSyncContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Enums\RuleType;
use App\Genie\Data\GenieDataDraftsResponses;
use App\Genie\Data\GenieDataFiles;
use App\Genie\Data\GenieDataIdeasResponses;
use App\Genie\Data\GenieDataPrePostsResponses;
use App\Genie\Data\GenieDataResponses;
use App\Genie\Data\GenieDataVectors;
use App\Genie\Data\GenieRunData;
use App\Genie\Data\GenieRunDataDrafts;
use App\Genie\Data\GenieRunDataIdeas;
use App\Genie\Data\GenieRunDataPrePosts;
use App\Models\Workspace;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inovector\Mixpost\Mixpost;

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
                case GenieType::RUN_RESPONSE:
                    return match ($action) {
                        GenieSyncAction::CREATE => new CreateResponse,
                    };
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

        $this->app->bind(GenieRunDataContract::class, function ($app, $params) {
            $model = $params['model'];
            $action = $params['action'];
            $type = $params['type'];

            switch ($type) {
                case RuleType::STRATEGY:
                    return new GenieRunData($model, $action);
                case RuleType::IDEAS:
                    return new GenieRunDataIdeas($model, $action);
                case RuleType::DRAFTS:
                    return new GenieRunDataDrafts($model, $action);
                case RuleType::PRE_POSTS:
                    return new GenieRunDataPrePosts($model, $action);
            }

        });

        $this->app->bind(GenieData::class, function ($app, $params) {
            $model = $params['model'];
            $action = $params['action'];
            $modelName = Str::snake(class_basename($model));
            $type = GenieType::fromName($modelName);

            switch ($type) {
                case GenieType::RUN_RESPONSE:
                    switch ($model->run->rule->rule_type) {
                        case RuleType::STRATEGY:
                            return new GenieDataResponses($model, $action);
                        case RuleType::IDEAS:
                            return new GenieDataIdeasResponses($model, $action);
                        case RuleType::DRAFTS:
                            return new GenieDataDraftsResponses($model, $action);
                        case RuleType::PRE_POSTS:
                            return new GenieDataPrePostsResponses($model, $action);
                    }
                case GenieType::FILE:
                    return new GenieDataFiles($model, $action);
                case GenieType::VECTOR:
                    return new GenieDataVectors($model, $action);
            }

        });

        $this->app->bind(GenieOutputContract::class, function ($app, $params) {
            $model = $params['model'];
            $type = $params['type'];

            switch ($type) {
                case GenieType::RUN_RESPONSE:
                    switch ($model->run->rule->rule_type) {
                        case RuleType::STRATEGY:
                            return new GenieOutputStrategy;
                        case RuleType::IDEAS:
                            return new GenieOutputIdeas;
                        case RuleType::DRAFTS:
                            return new GenieOutputDrafts;
                        case RuleType::PRE_POSTS:
                            return new GenieOutputPrePosts;
                    }
                case GenieType::FILE:
                case GenieType::VECTOR:
                    return new GenieOutput;
            }
        });

        $this->app->bind(GenieStateContract::class, function ($app, $params) {
            return match ($params['type']) {
                GenieType::FILE,
                GenieType::VECTOR => new GenieStateSyncs,
                GenieType::RUN_RESPONSE => new GenieStateRunResponses,
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
