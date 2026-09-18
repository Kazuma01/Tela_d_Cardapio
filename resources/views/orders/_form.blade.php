@csrf
@isset($order)
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm">
            @foreach (['pendente', 'em_preparo', 'pronto', 'entregue'] as $status)
                <option value="{{ $status }}" @selected($order->status === $status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </option>
            @endforeach
        </select>
    </div>
@endisset
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
        <div class="flex items-center justify-between border-b border-gray-100 py-3 gap-3">
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
                class="w-20 rounded-md border-gray-300 shadow-sm text-center">
        </div>
    @endforeach
@endforeach

<button type="submit" class="w-full bg-gray-900 text-white rounded-md py-2 mt-6 hover:bg-gray-700">
    {{ isset($order) ? 'Salvar alterações' : 'Enviar Pedido' }}
</button>