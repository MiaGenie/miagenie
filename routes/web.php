<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Mixpost;




Route::name('genie.')
    ->prefix('genie')
    ->middleware(array_merge(Mixpost::getWebAppMiddlewares(), Mixpost::getGlobalMiddlewares()))
    ->group(function () {
        // Dashboard routes
        Route::middleware(
            array_merge(
                Mixpost::getWebDashboardMiddlewares(),
                [HandleInertiaRequests::class]
            )
        )->group(function () {
            require __DIR__ . '/includes/genie.php';

            require __DIR__ . '/includes/genie-workspace.php';
        });
    });


Route::get('/', function () {
    return redirect()->to(config('mixpost.core_path'));
});
