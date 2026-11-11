<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompraRequest;
use App\Models\Compra;
use App\Models\Ingrediente;
use App\Services\CompraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function index(): View
    {
        $compras = Compra::query()
            ->with(['usuario', 'lotes.ingrediente'])
            ->latest('data_compra')
            ->paginate(20);

        return view('purchases.index', compact('compras'));
    }

    public function create(): View
    {
        $ingredientes = Ingrediente::query()
            ->where('status_ingrediente', 'ativo')
            ->orderBy('nome')
            ->get();

        return view('purchases.create', compact('ingredientes'));
    }

    public function store(StoreCompraRequest $request, CompraService $compraService): RedirectResponse
    {
        $compra = $compraService->registrarCompra($request->validated(), (int) Auth::id());

        return redirect()->route('purchases.index')
            ->with('success', "Compra #{$compra->id} registrada e estoque atualizado com sucesso.");
    }
}
