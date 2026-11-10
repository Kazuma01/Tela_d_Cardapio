@csrf
@if (isset($estoque))
    <div class="mb-4 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-md px-3 py-2 w-fit">
        <span class="text-lg leading-none">🥖</span>
        <span class="text-sm {{ $estoque > 0 ? 'text-amber-900' : 'text-red-700 font-bold' }}">
            @if ($estoque > 0)
                {{ $estoque }} {{ $estoque === 1 ? 'pão disponível' : 'pães disponíveis' }}
            @else
                Estoque de pão esgotado — reponha no painel da sala
            @endif
        </span>
    </div>
@endif
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Identificação (mesa, nome, etc.)</label>
    <input type="text" name="identificacao" value="{{ old('identificacao', $order->identificacao ?? '') }}"
        class="w-full rounded-md border-gray-300 shadow-sm">
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Observação geral</label>
    <textarea name="observacao" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('observacao', $order->observacao ?? '') }}</textarea>
</div>

@error('itens')
    <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
@enderror

@foreach ($categorias as $categoria)
    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ $categoria->nome }}</h4>

    @foreach ($categoria->products as $produto)
        @php
            $itemExistente = isset($order) ? $order->items->firstWhere('product_id', $produto->id) : null;
        @endphp
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 py-3 gap-3">
            <div class="flex-1">
                <p class="text-gray-800">{{ $produto->nome }}</p>
                <p class="text-sm text-gray-500">R$ {{ number_format($produto->preco, 2, ',', '.') }}</p>
                <input type="text" name="itens[{{ $produto->id }}][observacao]"
                    value="{{ $itemExistente->observacao ?? '' }}"
                    placeholder="Observação do item (opcional)"
                    class="mt-1 w-full text-sm rounded-md border-gray-300 shadow-sm">
            </div>
            <input type="number" name="itens[{{ $produto->id }}][quantidade]" min="0"
                value="{{ $itemExistente->quantidade ?? 0 }}"
                class="w-full sm:w-20 rounded-md border-gray-300 shadow-sm text-center">
        </div>
    @endforeach
@endforeach

<button type="submit" class="w-full bg-gray-900 text-white rounded-md py-2 mt-6 hover:bg-gray-700">
    {{ isset($order) ? 'Salvar alterações' : 'Enviar Pedido' }}
</button>