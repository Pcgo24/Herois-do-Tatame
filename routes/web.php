<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Home;
use App\Livewire\EnrollmentForm;

Route::get('/', Home::class)->name('home');
Route::get('/matricula', EnrollmentForm::class)->name('enrollment');

