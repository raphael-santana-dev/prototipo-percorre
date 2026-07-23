<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\UI\Livewire\Login;
use App\Modules\Dashboard\UI\Livewire\Dashboard;
use App\Modules\FeatureToggle\UI\Livewire\FeatureManager;
use App\Modules\ACL\UI\Livewire\RoleManager;
use App\Modules\ACL\UI\Livewire\PermissionManager;
use App\Modules\ACL\UI\Livewire\RolePermissionManager;
use App\Modules\Corporate\UI\Livewire\UserManager;
use App\Modules\Corporate\UI\Livewire\UserExtraPermissionManager;
use App\Modules\Student\UI\Livewire\Auth\Login as StudentLogin;
use App\Modules\Student\UI\Livewire\Dashboard\Dashboard as StudentDashboard;
use App\Modules\Student\UI\Livewire\Dashboard\Library as StudentLibrary;
use App\Modules\Turno\UI\Livewire\TurnoManager;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/features', FeatureManager::class)->name('features.index');
    Route::get('/roles', RoleManager::class)->name('roles.index');
    Route::get('/permissions', PermissionManager::class)->name('permissions.index');
    Route::get('/roles/{roleId}/permissions', RolePermissionManager::class)->name('roles.permissions');
    Route::get('/users', UserManager::class)->name('users.index');
    Route::get('/users/{userId}/extra-permissions', UserExtraPermissionManager::class)->name('users.extra-permissions');
    Route::get('/turnos', TurnoManager::class)->name('turnos.index')->middleware('feature:turno');
});

// ==========================================
// ÁREA DOS ALUNOS
// ==========================================
Route::prefix('alunos')->name('student.')->group(function () {
    
    // Visitantes (não logados como alunos)
    Route::middleware('guest:student')->group(function () {
        Route::get('/login', StudentLogin::class)->name('login');
    });

    // Alunos logados
    Route::middleware('auth:student')->group(function () {
        Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
        Route::get('/biblioteca', StudentLibrary::class)
            ->name('library')
            ->middleware('feature:alunos.biblioteca');
    });
});