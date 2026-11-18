<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de consumo de ingredientes · RecipeTool</title>
    <style>
        @page { margin: 32px 36px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .brand { color: #1d4ed8; font-size: 10px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .brand span { color: #64748b; font-weight: normal; letter-spacing: 0; text-transform: none; }
        h1 { font-size: 22px; margin: 8px 0 5px; }
        .meta { color: #64748b; font-size: 10px; margin: 0 0 24px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #eff6ff; color: #1e3a8a; font-size: 10px; text-align: left; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 9px 10px; }
        td:last-child, th:last-child { text-align: right; }
        .empty { background: #f8fafc; color: #64748b; padding: 14px; }
        .footer { border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 9px; margin-top: 22px; padding-top: 8px; }
    </style>
</head>
<body>
    @include('reports.pdf.partials.header', ['titulo' => 'Consumo de ingredientes'])

    @if ($linhas->isNotEmpty())
        <table>
            <thead>
                <tr><th>Ingrediente</th><th>Unidade</th><th>Quantidade consumida</th></tr>
            </thead>
            <tbody>
                @foreach ($linhas as $linha)
                    <tr>
                        <td>{{ $linha->nome }}</td>
                        <td>{{ $linha->unidade_medida }}</td>
                        <td>{{ number_format((float) $linha->quantidade_consumida, 3, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">Nenhum consumo encontrado para o período selecionado.</p>
    @endif

    <p class="footer">Documento gerado pelo RecipeTool.</p>
</body>
</html>
