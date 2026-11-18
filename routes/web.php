<?php

use App\Http\Controllers\AjusteEstoqueController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
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
            Route::post('/users/search', [UsuarioController::class, 'search'])
                ->name('users.search');
            Route::resource('users', UsuarioController::class)
                ->except(['show'])
                ->parameters(['users' => 'usuario'])
                ->names(['index' => 'users']);
        });

        Route::middleware('role:admin,gerente,funcionario')->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::post('/ingredients/search', [IngredienteController::class, 'search'])
                ->name('ingredients.search');
            Route::resource('ingredients', IngredienteController::class)
                ->parameters(['ingredients' => 'ingrediente'])
                ->names(['index' => 'ingredients']);
            Route::post('/products/search', [ProdutoController::class, 'search'])
                ->name('products.search');
            Route::resource('products', ProdutoController::class)
                ->parameters(['products' => 'produto'])
                ->names(['index' => 'products']);
            Route::post('/recipes/search', [ReceitaController::class, 'search'])
                ->name('recipes.search');
            Route::resource('recipes', ReceitaController::class)
                ->parameters(['recipes' => 'receita'])
                ->names(['index' => 'recipes']);
            Route::post('/orders/search', [PedidoController::class, 'search'])
                ->name('orders.search');
            Route::resource('orders', PedidoController::class)
                ->only(['index', 'create', 'store', 'show'])
                ->parameters(['orders' => 'pedido']);
            Route::post('/orders/{pedido}/cancel', [PedidoController::class, 'cancelar'])
                ->name('orders.cancel');
            Route::patch('/orders/{pedido}/status', [PedidoController::class, 'atualizarStatus'])
                ->name('orders.status.update');
            Route::resource('productions', ProducaoController::class)
                ->only(['index', 'create', 'store', 'show'])
                ->parameters(['productions' => 'producao']);
            Route::resource('stock-adjustments', AjusteEstoqueController::class)
                ->only(['index', 'create', 'store', 'show'])
                ->parameters(['stock-adjustments' => 'ajusteEstoque']);
            Route::resource('purchases', CompraController::class)
                ->only(['index', 'create', 'store']);

            Route::post('/stock/search', [EstoqueController::class, 'search'])
                ->name('stock.search');
            Route::get('/stock', [EstoqueController::class, 'index'])->name('stock.index');
            Route::get('/reports', [RelatorioController::class, 'index'])
                ->name('reports.index');
            Route::get('/reports/production.csv', [RelatorioController::class, 'production'])
                ->name('reports.production');
            Route::get('/reports/ingredient-consumption.csv', [RelatorioController::class, 'ingredientConsumption'])
                ->name('reports.ingredient-consumption');
            Route::get('/reports/executive.pdf', [RelatorioController::class, 'executivePdf'])
                ->name('reports.executive.pdf');
            Route::get('/reports/production.pdf', [RelatorioController::class, 'productionPdf'])
                ->name('reports.production.pdf');
            Route::get('/reports/ingredient-consumption.pdf', [RelatorioController::class, 'ingredientConsumptionPdf'])
                ->name('reports.ingredient-consumption.pdf');

            Route::view('/products/sales', 'products.sales')->name('products.sales');
            Route::view('/products/list', 'products.list')->name('products.list');
        });
    });
});
