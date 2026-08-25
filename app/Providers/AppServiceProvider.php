<?php

namespace App\Providers;

use App\Ai\Conversations\RecordingConversationStore;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inovector\Mixpost\Broadcast as MixpostBroadcast;
use Laravel\Ai\Contracts\ConversationStore;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        URL::forceHttps(
            $this->app->environment('staging', 'production')
        );

        // Laravel\Ai binds the stock store in its own register(); package providers register
        // before application ones, so this replaces it. The subclass only exists to keep the
        // message id that the stock store returns and RememberConversation throws away.
        $this->app->singleton(
            ConversationStore::class,
            fn (): RecordingConversationStore => new RecordingConversationStore(
                config('ai.conversations.connection'),
            ),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        MixpostBroadcast::routes();
    }
}
