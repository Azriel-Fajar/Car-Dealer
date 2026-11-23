<?php

use App\Http\Controllers\RoleHomeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'public/home')->name('home');

Route::get('/home', RoleHomeController::class)->middleware('auth')->name('role-home');

Route::inertia('/account', 'account/index')->middleware(['auth', 'role:customer'])->name('account');

Route::inertia('/dashboard', 'dashboard/index')->middleware(['auth', 'role:admin,sales'])->name('dashboard');

require __DIR__.'/dashboard.php';
require __DIR__.'/settings.php';
