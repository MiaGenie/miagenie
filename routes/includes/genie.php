<?php

use App\Http\Controllers\Admin\GenieFileController;
use App\Http\Controllers\Admin\VersionFieldsController;
use App\Http\Controllers\Admin\VersionsController;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Http\Base\Middleware\Admin;

Route::name('admin.')->prefix('admin')->middleware([Admin::class])->group(function () {

        Route::prefix('versions')->name('versions.')->group(function () {
            Route::get('/', [VersionsController::class, 'index'])->name('index');
            Route::get('create', [VersionsController::class, 'create'])->name('create');
            Route::post('store', [VersionsController::class, 'store'])->name('store');
            Route::get('{version}', [VersionsController::class, 'edit'])->name('edit');
            Route::put('{version}', [VersionsController::class, 'update'])->name('update');
            Route::delete('{version}', [VersionsController::class, 'destroy'])->name('delete');

            Route::prefix('{version}/fields')->name('fields.')->group(function () {
                Route::get('/', [VersionFieldsController::class, 'index'])->name('index');
                Route::get('create', [VersionFieldsController::class, 'create'])->name('create');
                Route::post('store', [VersionFieldsController::class, 'store'])->name('store');
                Route::get('{field}', [VersionFieldsController::class, 'edit'])->name('edit');
                Route::put('{field}', [VersionFieldsController::class, 'update'])->name('update');
                Route::post('positions', [VersionFieldsController::class, 'updatePositions'])->name('positions');
                Route::delete('{field}', [VersionFieldsController::class, 'destroy'])->name('delete');
            });
        });
});
