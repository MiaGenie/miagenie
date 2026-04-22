<?php

use App\Http\Controllers\MixpostEnterprise\Dashboard\Workspace\SettingsController;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Enums\WorkspaceUserRole;
use Inovector\Mixpost\Http\Base\Middleware\CheckWorkspaceUser;
use Inovector\Mixpost\Http\Base\Middleware\IdentifyWorkspace;

Route::middleware([
    IdentifyWorkspace::class,
    CheckWorkspaceUser::class . ':' . WorkspaceUserRole::ADMIN->name
])->prefix('{workspace}')
    ->name('workspace.')
    ->group(function () {
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/', [SettingsController::class, 'update'])->name('update');
        });
    });
