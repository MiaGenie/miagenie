<?php

use App\Http\Controllers\MixpostEnterprise\DashboardController;
use App\Http\Controllers\Workspace\BriefingsController;
use App\Http\Controllers\Workspace\DeleteIdeasController;
use App\Http\Controllers\Workspace\GenerateGeniePostsController;
use App\Http\Controllers\Workspace\GeniePostsController;
use App\Http\Controllers\Workspace\IdeasController;
use App\Http\Controllers\Workspace\RestoreIdeasController;
use App\Http\Controllers\Workspace\StrategiesController;
use App\Http\Controllers\Workspace\StrategyPresentationController;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Enums\WorkspaceUserRole;
use Inovector\Mixpost\Http\Base\Middleware\CheckWorkspaceUser;
use Inovector\Mixpost\Http\Base\Middleware\IdentifyWorkspace;
use Inovector\Mixpost\Mixpost;

Route::middleware(array_merge([
    IdentifyWorkspace::class,
    CheckWorkspaceUser::class,
], Mixpost::getWorkspaceMiddlewares()))
    ->prefix('{workspace}')
    ->group(function () {

        $editorMiddleware = CheckWorkspaceUser::class.':'.WorkspaceUserRole::ADMIN->name.'|'.WorkspaceUserRole::MEMBER->name;

        // dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');
        });

        // briefing
        Route::prefix('briefings')->name('briefings.')->middleware($editorMiddleware)->group(function () {
            Route::get('wizard', [BriefingsController::class, 'wizard'])->name('wizard');
            Route::post('wizard', [BriefingsController::class, 'wizardSave'])->name('wizard_save');
        });

        // strategy
        Route::prefix('strategies')->name('strategies.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('overview', [StrategyPresentationController::class, 'overview'])->name('overview')->withoutMiddleware($editorMiddleware);
            Route::post('generate', [StrategyPresentationController::class, 'generate'])->name('generate');
            Route::get('step-review', [StrategyPresentationController::class, 'review'])->name('step_review')->withoutMiddleware($editorMiddleware);
            Route::put('step-review/{runStep}', [StrategyPresentationController::class, 'approveReview'])->name('step_review_approve');
            Route::get('presentation', [StrategyPresentationController::class, 'index'])->name('presentation')->withoutMiddleware($editorMiddleware);
            Route::put('presentation/{strategy}', [StrategyPresentationController::class, 'update'])->name('presentation_update');
            Route::put('approve/{strategy}', [StrategiesController::class, 'approve'])->name('approve');
        });

        // idea
        Route::prefix('ideas')->name('ideas.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [IdeasController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [IdeasController::class, 'create'])->name('create');
            Route::post('generate', [IdeasController::class, 'generate'])->name('generate');
            Route::post('store', [IdeasController::class, 'store'])->name('store');
            Route::get('{idea}', [IdeasController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('{idea}', [IdeasController::class, 'update'])->name('update');
            Route::delete('{idea}', [IdeasController::class, 'destroy'])->name('delete');
            Route::delete('/', DeleteIdeasController::class)->name('deleteMultiple');
            Route::post('/', RestoreIdeasController::class)->name('restoreMultiple');
        });

        // posts produced from ideas: the draft itself lives in Mixpost, these only start and redo the generation
        Route::prefix('posts')->name('posts.')->middleware($editorMiddleware)->group(function () {
            Route::put('generate/{idea}', [GeniePostsController::class, 'generate'])->name('generate');
            Route::post('generate', GenerateGeniePostsController::class)->name('generateMultiple');
            Route::post('{post}/regenerate', [GeniePostsController::class, 'regenerateText'])->name('regenerateText');
            Route::post('{post}/images', [GeniePostsController::class, 'generateImages'])->name('generateImages');
            Route::post('{post}/images/{file}', [GeniePostsController::class, 'regenerateImage'])->name('regenerateImage');
            Route::post('{post}/images/{file}/reattach', [GeniePostsController::class, 'reattachImage'])->name('reattachImage');
            Route::post('{post}/images/{file}/restore', [GeniePostsController::class, 'restoreImage'])->name('restoreImage');
            Route::post('{post}/retry', [GeniePostsController::class, 'retry'])->name('retry');
        });
    });
