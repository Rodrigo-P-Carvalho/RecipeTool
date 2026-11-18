<header class="report-header">
    <p class="brand">RecipeTool <span>· Gestão de confeitaria</span></p>
    <h1>{{ $titulo }}</h1>
    <p class="meta">
        Período: {{ $periodo['de'] ? \Illuminate\Support\Carbon::parse($periodo['de'])->format('d/m/Y') : 'início' }}
        até {{ $periodo['ate'] ? \Illuminate\Support\Carbon::parse($periodo['ate'])->format('d/m/Y') : 'hoje' }}
        <span>· Gerado em {{ $geradoEm->format('d/m/Y H:i') }}</span>
    </p>
</header>
