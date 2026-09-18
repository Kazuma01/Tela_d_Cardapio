<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sala {{ $room->nome }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-50 rounded-md p-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500">Código da sala</p>
                    <p class="text-2xl font-bold tracking-widest">{{ $room->codigo }}</p>
                </div>
                <a href="{{ route('orders.create', $room) }}"
                    class="bg-gray-900 text-white rounded-md px-4 py-2 hover:bg-gray-700">
                    + Novo Pedido
                </a>
                <a href="{{ route('cozinha.index', $room) }}"
                    class="text-sm text-blue-600 hover:underline">
                    Ver cozinha
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold text-gray-900 mb-4">Pedidos</h3>

                @forelse ($room->orders as $order)
                    <div class="border border-gray-100 rounded-md p-4 mb-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold">
                                    {{ $order->identificacao ?: 'Sem identificação' }}
                                    <span class="text-xs font-normal text-gray-500">— {{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                </p>
                                <ul class="text-sm text-gray-600 mt-1">
                                    @foreach ($order->items as $item)
                                        <li>{{ $item->quantidade }}x {{ $item->product->nome }}
                                            @if ($item->observacao) <span class="text-gray-400">({{ $item->observacao }})</span> @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <a href="{{ route('orders.edit', [$room, $order]) }}" class="text-sm text-blue-600 hover:underline">
                                Editar
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Nenhum pedido ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>