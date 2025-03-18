<?php

namespace App\Providers;

use App\Configs\OpenAIConfig;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use OpenAI;
use OpenAI\Client;
use OpenAI\Contracts\ClientContract;
use App\Exceptions\OpenAIApiKeyMissing;

class OpenAIServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ClientContract::class, static function (): Client {
            $config = app(OpenAIConfig::class)->all();

            if (!is_string($config['api_key'])) {
                throw new OpenAIApiKeyMissing();
            }

            return OpenAI::factory()
                ->withApiKey($config['api_key'])
                ->withHttpClient(new \GuzzleHttp\Client(['timeout' => ($config['request_timeout'] ?? 30)]))
                ->make();
        });

        $this->app->alias(ClientContract::class, 'openai');
        $this->app->alias(ClientContract::class, Client::class);
    }

    /**
     * Bootstrap any application services.
     * @return void
     */
    public function boot(): void
    {

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            Client::class,
            ClientContract::class,
            'openai',
        ];
    }
}