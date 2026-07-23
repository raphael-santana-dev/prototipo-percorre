<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\UI\Livewire\Login;
use App\Modules\Dashboard\UI\Livewire\Dashboard;
use App\Modules\FeatureToggle\UI\Livewire\FeatureManager;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/features', FeatureManager::class)->name('features.index');
});