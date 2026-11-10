<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Vendas</title>
</head>
<body style="font-family: sans-serif; color: #1f2937;">

    <div style="text-align: center; margin-bottom: 24px;">
        <h1 style="font-size: 20px; margin: 0;">Histórico de Vendas</h1>
        <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0;">
            Sala {{ $room->nome }} — código {{ $room->codigo }}
            @if ($room->status === 'encerrada') (encerrada) @else (aberta) @endif
        </p>
        <p style="font-size: 10px; color: #9ca3af; margin: 4px 0 0;">
            Aberta em {{ $room->created_at->format('d/m/Y H:i') }} —
            Relatório gerado em {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>

    @if ($linhas->isEmpty())
        <p style="text-align: center; font-size: 13px; color: #6b7280;">
            Nenhum pedido registrado nesta sala ainda.
        </p>
    @else
        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
            <thead>
                <tr style="background-color: #1f2937; color: #ffffff;">
                    <th style="text-align: left; padding: 8px 10px;">Produto</th>
                    <th style="text-align: right; padding: 8px 10px;">Quantidade vendida</th>
                    <th style="text-align: right; padding: 8px 10px;">Valor total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($linhas as $linha)
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px 10px;">{{ $linha->produto }}</td>
                        <td style="padding: 8px 10px; text-align: right;">{{ $linha->quantidade }}</td>
                        <td style="padding: 8px 10px; text-align: right;">R$ {{ number_format($linha->total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td style="padding: 8px 10px;">Total geral</td>
                    <td style="padding: 8px 10px; text-align: right;">{{ $totalItens }}</td>
                    <td style="padding: 8px 10px; text-align: right;">R$ {{ number_format($totalGeral, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <p style="font-size: 11px; color: #6b7280; margin-top: 16px;">
            {{ $totalPedidos }} {{ $totalPedidos === 1 ? 'pedido registrado' : 'pedidos registrados' }} nesta sala.
            Todo pedido criado entra na contagem; pedidos excluídos saem do histórico.
        </p>
    @endif

</body>
</html>
