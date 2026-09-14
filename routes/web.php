<?php

use App\Http\Controllers\SignedFichaController;
use App\Http\Controllers\StudentFichaController;
use App\Livewire\Admin\ChangePassword;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Users;
use App\Livewire\Auth\Login;
use App\Livewire\EnrollmentForm;
use App\Livewire\Home;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');
Route::get('/enrollment', EnrollmentForm::class)->name('enrollment');
Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', Dashboard::class)->middleware('can:view-students')->name('admin.dashboard');
    Route::get('/admin/senha', ChangePassword::class)->name('admin.password');
    Route::get('/admin/usuarios', Users::class)->name('admin.users');

    Route::get('/admin/alunos/{student}/ficha', StudentFichaController::class)
        ->middleware('can:view-students')
        ->name('admin.students.ficha');

    Route::get('/admin/alunos/{student}/ficha-assinada', SignedFichaController::class)
        ->middleware('can:view-students')
        ->name('admin.students.ficha-assinada');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
