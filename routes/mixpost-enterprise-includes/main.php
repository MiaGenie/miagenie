<?php

use App\Http\Controllers\MixpostEnterprise\Dashboard\Main\CreateWorkspaceController;
use Illuminate\Support\Facades\Route;

use Inovector\MixpostEnterprise\Http\Base\Middleware\AllowMultipleWorkspaces;


Route::middleware([AllowMultipleWorkspaces::class])
    ->prefix('workspace')
    ->name('workspace.')
    ->group(function () {
        Route::get('create', [CreateWorkspaceController::class, 'create'])->name('create');
        Route::post('store', [CreateWorkspaceController::class, 'store'])->name('store');
    });
