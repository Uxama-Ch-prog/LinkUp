<?php

use Illuminate\Support\Facades\Route;

// Add named routes for authentication
Route::get('/login', function () {
    return view('app');
})->name('login');

Route::get('/register', function () {
    return view('app');
})->name('register');

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
