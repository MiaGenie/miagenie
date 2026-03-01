<?php

use App\Http\Controllers\MixpostEnterprise\Panel\WorkspacesController;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Http\Base\Middleware\Admin;


Route::middleware([Admin::class])->group(function () {

    Route::prefix('workspaces')->name('workspaces.')->group(function () {
        Route::get('create', [WorkspacesController::class, 'create'])->name('create');
        Route::post('/', [WorkspacesController::class, 'store'])->name('store');
        Route::get('{workspace}', [WorkspacesController::class, 'view'])->name('view');
        Route::get('{workspace}/edit', [WorkspacesController::class, 'edit'])->name('edit');
        Route::put('{workspace}', [WorkspacesController::class, 'update'])->name('update');
    });

});
