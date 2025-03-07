<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(config('mixpost.core_path'));
});
