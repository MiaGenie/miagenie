<?php

use App\Http\Controllers\Workspace\CompetitorsController;
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
    });