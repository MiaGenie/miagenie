<?php

use App\Http\Controllers\MixpostEnterprise\DashboardController;
use App\Http\Controllers\Workspace\HomeController;
use App\Http\Controllers\Workspace\PostsController;
use App\Http\Controllers\Workspace\TagsController;
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
            require __DIR__.'/includes/genie.php';

            require __DIR__.'/includes/genie-workspace.php';
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
            Route::get('/', HomeController::class)->name('home');

            Route::middleware(array_merge([
                IdentifyWorkspace::class,
                CheckWorkspaceUser::class,
            ], Mixpost::getWorkspaceMiddlewares()))
                ->prefix('{workspace}')
                ->group(function () {
                    $editorMiddleware = CheckWorkspaceUser::class.':'.WorkspaceUserRole::ADMIN->name.'|'.WorkspaceUserRole::MEMBER->name;

                    Route::get('/', DashboardController::class)->name('dashboard');

                    // posts
                    Route::prefix('posts')->name('posts.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
                        Route::get('create/{schedule_at?}', [PostsController::class, 'create'])
                            ->name('create')
                            ->where('schedule_at', '^(\d{4})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]) (0\d|1\d|2[0-3]):([0-5]\d)$');
                        Route::get('{post}', [PostsController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
                        // Same method + URI as the package route, so this wins: the Genie tag is kept where Genie put it.
                        Route::put('{post}', [PostsController::class, 'update'])->name('update');
                    });

                    // tags: same method + URI as the package routes, so these win; the Genie tag is protected
                    Route::prefix('tags')->name('tags.')->middleware($editorMiddleware)->group(function () {
                        Route::post('/', [TagsController::class, 'store'])->name('store');
                        Route::put('{tag}', [TagsController::class, 'update'])->name('update');
                        Route::delete('{tag}', [TagsController::class, 'destroy'])->name('delete');
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
            require __DIR__.'/mixpost-enterprise-includes/panel.php';
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
            require __DIR__.'/mixpost-enterprise-includes/main.php';
            require __DIR__.'/mixpost-enterprise-includes/workspace.php';
        });
    });

Route::get('/', function () {
    return redirect()->to(config('mixpost.core_path'));
});
