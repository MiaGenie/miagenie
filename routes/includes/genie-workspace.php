<?php

use App\Http\Controllers\Workspace\CompetitorsController;
use App\Http\Controllers\Workspace\BriefingsController;
use App\Http\Controllers\Workspace\DeleteIdeasController;
use App\Http\Controllers\Workspace\DraftsController;
use App\Http\Controllers\Workspace\IdeasController;
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
            Route::put('{briefing}', [BriefingsController::class, 'update'])->name('update');
            Route::delete('{briefing}', [BriefingsController::class, 'destroy'])->name('delete');
        });

        // strategy
        Route::prefix('strategies')->name('strategies.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [StrategiesController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [StrategiesController::class, 'create'])->name('create');
            Route::post('store', [StrategiesController::class, 'store'])->name('store');
            Route::get('edit/{strategy}', [StrategiesController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::get('review/{strategy}', [StrategiesController::class, 'review'])->name('review')->withoutMiddleware($editorMiddleware);
            Route::put('{strategy}', [StrategiesController::class, 'review_update'])->name('review_update');
            Route::delete('{strategy}', [StrategiesController::class, 'destroy'])->name('delete');
        });

        // idea
        Route::prefix('ideas')->name('ideas.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [IdeasController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [IdeasController::class, 'create'])->name('create');
            Route::put('generate/{strategy}', [IdeasController::class, 'generate'])->name('generate');
            Route::post('store', [IdeasController::class, 'store'])->name('store');
            Route::get('{idea}', [IdeasController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('{idea}', [IdeasController::class, 'update'])->name('update');
            Route::delete('{idea}', [IdeasController::class, 'destroy'])->name('delete');
            Route::delete('/', DeleteIdeasController::class)->name('deleteMultiple');
        });

        Route::prefix('drafts')->name('drafts.')->middleware($editorMiddleware)->group(function () use ($editorMiddleware) {
            Route::get('/', [DraftsController::class, 'index'])->name('index')->withoutMiddleware($editorMiddleware);
            Route::get('create', [DraftsController::class, 'create'])->name('create');
            Route::put('generate/{idea}', [DraftsController::class, 'generate'])->name('generate');
            Route::post('store', [DraftsController::class, 'store'])->name('store');
            Route::get('{draft}', [DraftsController::class, 'edit'])->name('edit')->withoutMiddleware($editorMiddleware);
            Route::put('{draft}', [DraftsController::class, 'update'])->name('update');
            Route::delete('{draft}', [DraftsController::class, 'destroy'])->name('delete');
        });
    });
