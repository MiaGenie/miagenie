<?php

use App\Http\Controllers\MixpostEnterprise\DashboardController;
use App\Http\Controllers\Workspace\PostsController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Enums\WorkspaceUserRole;
use Inovector\Mixpost\Http\Base\Middleware\CheckWorkspaceUser;
use Inovector\Mixpost\Http\Base\Middleware\IdentifyWorkspace;
use Inovector\Mixpost\Http\Base\Middleware\Localization;
use Inovector\Mixpost\Mixpost;
use Inovector\Mixpost\Util;
use Inovector\MixpostEnterprise\Util as EnterpriseUtil;

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

Route::prefix(Util::corePath())
    ->name('mixpost.')
    ->middleware(array_merge(Mixpost::getWebAppMiddlewares(), Mixpost::getGlobalMiddlewares()))
    ->group(function () {

        // Dashboard routes
        Route::middleware(array_merge(
            Mixpost::getWebDashboardMiddlewares(),
            [HandleInertiaRequests::class]
        ))->group(function () {
            Route::get('/', \App\Http\Controllers\Workspace\HomeController::class)->name('home');


            Route::middleware(array_merge([
                IdentifyWorkspace::class,
                CheckWorkspaceUser::class
            ], Mixpost::getWorkspaceMiddlewares()))
                ->prefix('{workspace}')
                ->group(function () {
                    $editorMiddleware = CheckWorkspaceUser::class . ':' . WorkspaceUserRole::ADMIN->name . '|' . WorkspaceUserRole::MEMBER->name;

                    Route::get('/', DashboardController::class)->name('dashboard');

                    // posts
                    Route::prefix('posts')->name('posts.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
                        Route::get('{post}', [PostsController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
                    });
                });

        });
    });

Route::prefix(EnterpriseUtil::corePath(true))
    ->name('mixpost_e.')
    ->middleware(Mixpost::getWebAppMiddlewares())
    ->group(function () {
        // Enterprise console routes
        Route::middleware(array_merge(
            Mixpost::getWebDashboardMiddlewares(),
            [HandleInertiaRequests::class]
        ))->group(function () {
            require __DIR__ . '/mixpost-includes/panel.php';
        });
    });

Route::prefix(Util::corePath())
    ->name('mixpost_e.')
    ->middleware(['web', Localization::class])
    ->group(function () {
        // Dashboard routes
        Route::middleware(array_merge(
            Mixpost::getWebDashboardMiddlewares(),
            [HandleInertiaRequests::class]
        ))->group(function () {
            require __DIR__ . '/mixpost-includes/workspace.php';
        });
    });

Route::get('/', function () {
    return redirect()->to(config('mixpost.core_path'));
});
