<?php

use App\Http\Controllers\Dashboard\BrandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::resource('brands', BrandController::class)->except(['create', 'show', 'edit']);
});
