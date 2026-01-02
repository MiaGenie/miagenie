<?php

use App\Http\Controllers\Workspace\CompetitorsController;
use App\Http\Controllers\Workspace\BriefingsController;
use App\Http\Controllers\Workspace\DashboardController;
use App\Http\Controllers\Workspace\ConfigController;
use App\Http\Controllers\Workspace\DeleteDraftsController;
use App\Http\Controllers\Workspace\DeleteIdeasController;
use App\Http\Controllers\Workspace\DraftsController;
use App\Http\Controllers\Workspace\IdeaDraftsController;
use App\Http\Controllers\Workspace\IdeasController;
use App\Http\Controllers\Workspace\PrePostsController;
use App\Http\Controllers\Workspace\StrategiesController;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Enums\WorkspaceUserRole;
use Inovector\Mixpost\Http\Base\Middleware\CheckWorkspaceUser;
use Inovector\Mixpost\Http\Base\Middleware\IdentifyWorkspace;
use Inovector\Mixpost\Mixpost;

Route::middleware(array_merge([
    IdentifyWorkspace::class,
    CheckWorkspaceUser::class
], Mixpost::getWorkspaceMiddlewares()))
    ->prefix('{workspace}')
    ->group(function () {

        $editorMiddleware = CheckWorkspaceUser::class . ':' . WorkspaceUserRole::ADMIN->name . '|' . WorkspaceUserRole::MEMBER->name;

        // dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');
        });

        Route::prefix('config')->name('config.')->group(function () {
            Route::get('/', ConfigController::class)->name('config');
        });

        // competitor
        Route::prefix('competitors')->name('competitors.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [CompetitorsController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [CompetitorsController::class, 'create'])->name('create');
            Route::post('store', [CompetitorsController::class, 'store'])->name('store');
            Route::get('{competitor}', [CompetitorsController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('{competitor}', [CompetitorsController::class, 'update'])->name('update');
            Route::delete('{competitor}', [CompetitorsController::class, 'destroy'])->name('delete');
        });

        // briefing
        Route::prefix('briefings')->name('briefings.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [BriefingsController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [BriefingsController::class, 'create'])->name('create');
            Route::post('store', [BriefingsController::class, 'store'])->name('store');
            Route::get('{briefing}', [BriefingsController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::post('update/{briefing}', [BriefingsController::class, 'update'])->name('update');
            Route::delete('{briefing}', [BriefingsController::class, 'destroy'])->name('delete');
        });

        // strategy
        Route::prefix('strategies')->name('strategies.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [StrategiesController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('list', [StrategiesController::class, 'list'])->name('list')->withoutMiddleware($editorMiddleware);
            Route::get('create', [StrategiesController::class, 'create'])->name('create');
            Route::post('store', [StrategiesController::class, 'store'])->name('store');
            Route::get('edit/{strategy}', [StrategiesController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('update/{strategy}', [StrategiesController::class, 'update'])->name('update');
            Route::get('review/{strategy}', [StrategiesController::class, 'review'])->name('review')->withoutMiddleware($editorMiddleware);
            Route::put('review_update/{strategy}', [StrategiesController::class, 'review_update'])->name('review_update');
            Route::delete('{strategy}', [StrategiesController::class, 'destroy'])->name('delete');
        });

        // idea
        Route::prefix('ideas')->name('ideas.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [IdeasController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [IdeasController::class, 'create'])->name('create');
            Route::put('generate/{strategy}', [IdeasController::class, 'generate'])->name('generate');
            Route::put('updateGenerate/{idea}', [IdeasController::class, 'updateGenerate'])->name('updateGenerate');
            Route::post('store', [IdeasController::class, 'store'])->name('store');
            Route::get('{idea}', [IdeasController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('{idea}', [IdeasController::class, 'update'])->name('update');
            Route::delete('{idea}', [IdeasController::class, 'destroy'])->name('delete');
            Route::delete('/', DeleteIdeasController::class)->name('deleteMultiple');
            Route::get('{idea}/ideaDrafts', IdeaDraftsController::class)->name('ideaDrafts');
        });

        Route::prefix('drafts')->name('drafts.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [DraftsController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [DraftsController::class, 'create'])->name('create');
            Route::put('generate/{idea}', [DraftsController::class, 'generate'])->name('generate');
            Route::post('store', [DraftsController::class, 'store'])->name('store');
            Route::put('updateGenerate/{draft}', [DraftsController::class, 'updateGenerate'])->name('updateGenerate');
            Route::get('{draft}', [DraftsController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('{draft}', [DraftsController::class, 'update'])->name('update');
            Route::delete('{draft}', [DraftsController::class, 'destroy'])->name('delete');
            Route::post('generateMultiple', [DraftsController::class, 'generateMultiple'])->name('generateMultiple');
            Route::delete('/', DeleteDraftsController::class)->name('deleteMultiple');
        });

        Route::prefix('pre_posts')->name('pre_posts.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [PrePostsController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [PrePostsController::class, 'create'])->name('create');
            Route::put('generate/{draft}', [PrePostsController::class, 'generate'])->name('generate');
            Route::post('store', [PrePostsController::class, 'store'])->name('store');
            Route::post('updateGenerate', [PrePostsController::class, 'updateGenerate'])->name('updateGenerate');
            Route::get('{pre_post}', [PrePostsController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('{pre_post}', [PrePostsController::class, 'update'])->name('update');
            Route::delete('{pre_post}', [PrePostsController::class, 'destroy'])->name('delete');
            Route::post('generateMultiple', [PrePostsController::class, 'generateMultiple'])->name('generateMultiple');
        });
    });
