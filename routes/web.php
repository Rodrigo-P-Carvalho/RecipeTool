<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::view('/products', 'products')
    ->middleware('auth')
    ->name('products');

Route::view('/products/sales', 'products.sales')
    ->middleware('auth')
    ->name('products.sales');

Route::view('/products/list', 'products.list')
    ->middleware('auth')
    ->name('products.list');

Route::view('/recipes', 'recipes')
    ->middleware('auth')
    ->name('recipes');

Route::view('/ingredients', 'ingredients')
    ->middleware('auth')
    ->name('ingredients');

Route::view('/users', 'users')
    ->middleware('auth')
    ->name('users');
