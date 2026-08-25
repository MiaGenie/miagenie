<?php

use App\Http\Controllers\Admin\ConfigsController;
use App\Http\Controllers\Admin\FilesController;
use App\Http\Controllers\Admin\LogsController;
use App\Http\Controllers\Admin\ModelProfilesController;
use App\Http\Controllers\Admin\PlanInfoController;
use App\Http\Controllers\Admin\RulesController;
use App\Http\Controllers\Admin\RuleStepsController;
use App\Http\Controllers\Admin\RunsController;
use App\Http\Controllers\Admin\RunStepsController;
use App\Http\Controllers\Admin\VectorsController;
use App\Http\Controllers\Admin\VersionFieldOptionsController;
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
        Route::put('update/{version}', [VersionsController::class, 'update'])->name('update');
        Route::put('clone/{version}', [VersionsController::class, 'clone'])->name('clone');
        Route::delete('{version}', [VersionsController::class, 'destroy'])->name('delete');

        Route::prefix('{version}/fields')->name('fields.')->group(function () {
            Route::get('/', [VersionFieldsController::class, 'index'])->name('index');
            Route::get('index-translate', [VersionFieldsController::class, 'indexTranslate'])->name('index-translate');
            Route::get('create', [VersionFieldsController::class, 'create'])->name('create');
            Route::post('store', [VersionFieldsController::class, 'store'])->name('store');
            Route::get('{field}', [VersionFieldsController::class, 'edit'])->name('edit');
            Route::get('translate-field/{field}/{locale}', [VersionFieldsController::class, 'translate'])->name('translate-field');
            Route::get('translate-options/{field}/{locale}', [VersionFieldOptionsController::class, 'translate'])->name('translate-options');
            Route::put('{field}', [VersionFieldsController::class, 'update'])->name('update');
            Route::put('update-translation-field/{field}/{locale}', [VersionFieldsController::class, 'updateTranslations'])->name('update-translation-field');
            Route::put('update-translation-options/{field}/{locale}', [VersionFieldOptionsController::class, 'updateTranslations'])->name('update-translation-options');
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
            Route::put('clone/{rule}', [RulesController::class, 'clone'])->name('clone');
            Route::put('clone_into/{rule}/{target}', [RulesController::class, 'cloneInto'])->name('clone_into');

            Route::prefix('{rule}/steps')->name('steps.')->group(function () {
                Route::get('/', [RuleStepsController::class, 'index'])->name('index');
                Route::get('index-translate', [RuleStepsController::class, 'indexTranslate'])->name('index-translate');
                Route::get('create', [RuleStepsController::class, 'create'])->name('create');
                Route::post('store', [RuleStepsController::class, 'store'])->name('store');
                Route::get('{step}', [RuleStepsController::class, 'edit'])->name('edit');
                Route::get('translate/{step}/{locale}', [RuleStepsController::class, 'translate'])->name('translate');
                Route::put('{step}', [RuleStepsController::class, 'update'])->name('update');
                Route::put('update-translation/{step}/{locale}', [RuleStepsController::class, 'updateTranslations'])->name('update-translation');
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

    Route::prefix('model_profiles')->name('model_profiles.')->group(function () {
        Route::get('/', [ModelProfilesController::class, 'index'])->name('index');
        Route::get('create', [ModelProfilesController::class, 'create'])->name('create');
        Route::post('store', [ModelProfilesController::class, 'store'])->name('store');
        Route::get('{model_profile}', [ModelProfilesController::class, 'edit'])->name('edit');
        Route::put('{model_profile}', [ModelProfilesController::class, 'update'])->name('update');
        Route::delete('{model_profile}', [ModelProfilesController::class, 'destroy'])->name('delete');
    });

    Route::prefix('files')->name('files.')->group(function () {
        Route::get('/', [FilesController::class, 'index'])->name('index');
        Route::delete('/', [FilesController::class, 'destroy'])->name('delete');
        Route::post('upload', [FilesController::class, 'upload'])->name('upload');
        Route::get('{file}', [FilesController::class, 'download'])->name('download');
        Route::get('fetch/uploaded', [FilesController::class, 'fetchUploads'])->name('fetchUploads');
    });

    Route::prefix('runs')->name('runs.')->group(function () {
        Route::get('/', [RunsController::class, 'index'])->name('index');
        Route::put('{run}', [RunsController::class, 'resume'])->name('resume');

        Route::prefix('{run}/steps')->name('steps.')->group(function () {
            Route::get('/', [RunStepsController::class, 'index'])->name('index');
            Route::get('{step}', [RunStepsController::class, 'view'])->name('view');
            Route::delete('{step}', [RunStepsController::class, 'destroy'])->name('delete');
        });
    });

    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogsController::class, 'index'])->name('index');
        Route::get('/{log}', [LogsController::class, 'view'])->name('view');
    });

    Route::prefix('configs')->name('configs.')->group(function () {
        Route::get('/', [ConfigsController::class, 'form'])->name('form');
        Route::put('/', [ConfigsController::class, 'update'])->name('update');
    });

    Route::prefix('plans_info')->name('plans_info.')->group(function () {
        Route::get('/', [PlanInfoController::class, 'index'])->name('index');
        Route::get('{plan_id}/{locale}', [PlanInfoController::class, 'edit'])->name('edit');
        Route::put('update/{plan_id}/{locale}', [PlanInfoController::class, 'update'])->name('update');
    });

});
