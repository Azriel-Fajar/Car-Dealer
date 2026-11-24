<?php

use App\Http\Controllers\Dashboard\BrandController;
use App\Http\Controllers\Dashboard\CarColorController;
use App\Http\Controllers\Dashboard\CarController;
use App\Http\Controllers\Dashboard\CarHotspotController;
use App\Http\Controllers\Dashboard\CarImageController;
use App\Http\Controllers\Dashboard\CarOptionController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ShowroomController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::resource('brands', BrandController::class)->except(['create', 'show', 'edit']);
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);
    Route::resource('showrooms', ShowroomController::class)->except(['create', 'show', 'edit']);
    Route::resource('cars', CarController::class)->except('show');
    Route::patch('cars/{car}/restore', [CarController::class, 'restore'])->withTrashed()->name('cars.restore');

    // Child records of a car: a child id from another car resolves to 404.
    Route::scopeBindings()->prefix('cars/{car}')->name('cars.')->group(function () {
        Route::post('colors', [CarColorController::class, 'store'])->name('colors.store');
        Route::put('colors/{color}', [CarColorController::class, 'update'])->name('colors.update');
        Route::delete('colors/{color}', [CarColorController::class, 'destroy'])->name('colors.destroy');

        Route::post('options', [CarOptionController::class, 'store'])->name('options.store');
        Route::put('options/{option}', [CarOptionController::class, 'update'])->name('options.update');
        Route::delete('options/{option}', [CarOptionController::class, 'destroy'])->name('options.destroy');

        Route::post('images', [CarImageController::class, 'store'])->name('images.store');
        Route::patch('images/order', [CarImageController::class, 'order'])->name('images.order');
        Route::patch('images/{image}/cover', [CarImageController::class, 'cover'])->name('images.cover');
        Route::delete('images/{image}', [CarImageController::class, 'destroy'])->name('images.destroy');

        Route::put('hotspots', [CarHotspotController::class, 'update'])->name('hotspots.update');
    });

    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
});
