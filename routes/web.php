<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\EnrollmentForm;
use App\Livewire\Home;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');
Route::get('/enrollment', EnrollmentForm::class)->name('enrollment');
Route::get('/login', fn () => redirect('/'))->name('login');

// TODO: proteger com middleware 'auth' quando o login for implementado.
Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
