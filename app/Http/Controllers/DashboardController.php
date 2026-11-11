<?php

namespace App\Http\Controllers;

use App\Models\LoteIngrediente;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $lotesComAlertas = LoteIngrediente::query()
            ->with('ingrediente')
            ->withExpiryAlerts()
            ->orderBy('data_validade')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return view('dashboard', compact('lotesComAlertas'));
    }
}
