<?php

use App\Http\Controllers\AjusteEstoqueController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\IngredienteController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProducaoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ReceitaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'createRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');

    Route::get('/forgot-password', [AuthController::class, 'createPasswordRequest'])
        ->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'createPasswordReset'])
        ->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/trocar-senha', [AuthController::class, 'createForcedPasswordChange'])
        ->name('password.change');
    Route::put('/trocar-senha', [AuthController::class, 'updateForcedPassword'])
        ->name('password.change.update');

    Route::middleware('password.change')->group(function () {
        Route::middleware('role:admin,gerente')->group(function () {
            Route::resource('users', UsuarioController::class)
                ->except(['show'])
                ->parameters(['users' => 'usuario'])
                ->names(['index' => 'users']);
        });

        Route::middleware('role:admin,gerente,funcionario')->group(function () {
            Route::view('/dashboard', 'dashboard')->name('dashboard');

            Route::resource('ingredients', IngredienteController::class)
                ->parameters(['ingredients' => 'ingrediente'])
                ->names(['index' => 'ingredients']);
            Route::resource('products', ProdutoController::class)
                ->parameters(['products' => 'produto'])
                ->names(['index' => 'products']);
            Route::resource('recipes', ReceitaController::class)
                ->parameters(['recipes' => 'receita'])
                ->names(['index' => 'recipes']);
            Route::resource('orders', PedidoController::class)
                ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
                ->parameters(['orders' => 'pedido']);
            Route::resource('productions', ProducaoController::class)
                ->only(['index', 'create', 'store', 'show'])
                ->parameters(['productions' => 'producao']);
            Route::resource('stock-adjustments', AjusteEstoqueController::class)
                ->only(['index', 'create', 'store', 'show'])
                ->parameters(['stock-adjustments' => 'ajusteEstoque']);

            Route::get('/stock', [EstoqueController::class, 'index'])->name('stock.index');
            Route::get('/reports/production.csv', [RelatorioController::class, 'production'])
                ->name('reports.production');
            Route::get('/reports/ingredient-consumption.csv', [RelatorioController::class, 'ingredientConsumption'])
                ->name('reports.ingredient-consumption');

            Route::view('/products/sales', 'products.sales')->name('products.sales');
            Route::view('/products/list', 'products.list')->name('products.list');
        });
    });
});
