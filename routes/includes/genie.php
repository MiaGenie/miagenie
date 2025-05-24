<?php

use App\Http\Controllers\Admin\AssistantsController;
use App\Http\Controllers\Admin\AIModelsController;
use App\Http\Controllers\Admin\FilesController;
use App\Http\Controllers\Admin\ConfigsController;
use App\Http\Controllers\Admin\RulesController;
use App\Http\Controllers\Admin\RuleStepsController;
use App\Http\Controllers\Admin\ThreadsController;
use App\Http\Controllers\Admin\VectorsController;
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

        Route::prefix('{version}/rules')->name('rules.')->group(function () {
            Route::get('/', [RulesController::class, 'index'])->name('index');
            Route::get('create', [RulesController::class, 'create'])->name('create');
            Route::post('store', [RulesController::class, 'store'])->name('store');
            Route::get('{rule}', [RulesController::class, 'edit'])->name('edit');
            Route::put('{rule}', [RulesController::class, 'update'])->name('update');
            Route::delete('{rule}', [RulesController::class, 'destroy'])->name('delete');

            Route::prefix('{rule}/steps')->name('steps.')->group(function () {
                Route::get('/', [RuleStepsController::class, 'index'])->name('index');
                Route::get('create', [RuleStepsController::class, 'create'])->name('create');
                Route::post('store', [RuleStepsController::class, 'store'])->name('store');
                Route::get('{step}', [RuleStepsController::class, 'edit'])->name('edit');
                Route::put('{step}', [RuleStepsController::class, 'update'])->name('update');
                Route::post('positions', [RuleStepsController::class, 'updatePositions'])->name('positions');
                Route::delete('{step}', [RuleStepsController::class, 'destroy'])->name('delete');
            });
        });
    });

    Route::prefix('vectors')->name('vectors.')->group(function () {
        Route::get('/', [VectorsController::class, 'index'])->name('index');
        Route::get('create', [VectorsController::class, 'create'])->name('create');
        Route::post('store', [VectorsController::class, 'store'])->name('store');
        Route::get('{vector}', [VectorsController::class, 'edit'])->name('edit');
        Route::put('{vector}', [VectorsController::class, 'update'])->name('update');
        Route::delete('{vector}', [VectorsController::class, 'destroy'])->name('delete');
    });

    Route::prefix('ai_models')->name('ai_models.')->group(function () {
        Route::get('/', [AIModelsController::class, 'index'])->name('index');
        Route::get('create', [AIModelsController::class, 'create'])->name('create');
        Route::post('store', [AIModelsController::class, 'store'])->name('store');
        Route::get('{ai_model}', [AIModelsController::class, 'edit'])->name('edit');
        Route::put('{ai_model}', [AIModelsController::class, 'update'])->name('update');
        Route::delete('{ai_model}', [AIModelsController::class, 'destroy'])->name('delete');
    });

    Route::prefix('assistants')->name('assistants.')->group(function () {
        Route::get('/', [AssistantsController::class, 'index'])->name('index');
        Route::get('create', [AssistantsController::class, 'create'])->name('create');
        Route::post('store', [AssistantsController::class, 'store'])->name('store');
        Route::get('{assistant}', [AssistantsController::class, 'edit'])->name('edit');
        Route::put('{assistant}', [AssistantsController::class, 'update'])->name('update');
        Route::delete('{assistant}', [AssistantsController::class, 'destroy'])->name('delete');
    });

    Route::prefix('files')->name('files.')->group(function () {
        Route::get('/', [FilesController::class, 'index'])->name('index');
        Route::delete('/', [FilesController::class, 'destroy'])->name('delete');
        Route::post('upload', [FilesController::class, 'upload'])->name('upload');
        Route::get('{file}', [FilesController::class, 'download'])->name('download');
        Route::get('fetch/uploaded', [FilesController::class, 'fetchUploads'])->name('fetchUploads');
    });

    Route::prefix('threads')->name('threads.')->group(function () {
        Route::get('/', [ThreadsController::class, 'index'])->name('index');
        Route::get('{thread}', [ThreadsController::class, 'view'])->name('view');
    });

    Route::prefix('configs')->name('configs.')->group(function () {
        Route::get('/', [ConfigsController::class, 'form'])->name('form');
        Route::put('/', [ConfigsController::class, 'update'])->name('update');
    });

});
