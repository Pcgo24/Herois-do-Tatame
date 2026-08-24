<?php

use App\Http\Controllers\StudentFichaController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Auth\Login;
use App\Livewire\EnrollmentForm;
use App\Livewire\Home;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');
Route::get('/enrollment', EnrollmentForm::class)->name('enrollment');
Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');

    Route::get('/admin/alunos/{student}/ficha', StudentFichaController::class)
        ->name('admin.students.ficha');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
