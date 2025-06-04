<?php

namespace App\Providers;

use App\Actions\CreateThread;
use App\Actions\GenieOutput\ThreadOutput;
use App\Actions\MessageThread;
use App\Actions\UpdateThread;
use App\Actions\StatusThread;
use App\Contracts\GenieOutputContract;
use App\Contracts\ThreadAction;
use App\Abstracts\GenieData;
use App\GenieData\Thread\Analysis;
use App\GenieData\Thread\Channels;
use App\GenieData\Thread\Content;
use App\GenieData\Thread\Ideas;
use App\GenieData\Thread\Schedule;
use Illuminate\Support\ServiceProvider;

class GenieServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(ThreadAction::class, function ($app, $params) {

            $thread = $params['thread'];

            return match($params['action']) {
                'create' => new CreateThread($thread),
                'update' => new UpdateThread($thread),
                'status' => new StatusThread($thread),
                'message' => new MessageThread($thread),
            };

        });

        $this->app->bind(GenieData::class, function ($app, $params) {
            $action = $params['action'];
            $model = $params['model'];
            return match($params['rule_type']) {
                'ANALYSIS' => new Analysis($action, $model),
                'CHANNELS' => new Channels($action, $model),
                'IDEAS' => new Ideas($action, $model),
                'CONTENT' => new Content($action, $model),
                'SCHEDULE' => new Schedule($action, $model),
            };

        });

        $this->app->bind(GenieOutputContract::class, function ($app, $params) {

            $data = $params['data'];

            return match($params['type']) {
                'THREAD' => new ThreadOutput($data),
                'FILE' => new Channels($data),
                'VECTOR' => new Ideas($data),
                'ASSISTANT' => new Content($data),
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




