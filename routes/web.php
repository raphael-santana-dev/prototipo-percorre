<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\UI\Livewire\Login;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', Login::class)->name('login')->middleware('guest');
