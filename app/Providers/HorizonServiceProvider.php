<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Inovector\Mixpost\Models\Admin;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return Admin::where('user_id', $user->id)->exists();
        });
    }
}
