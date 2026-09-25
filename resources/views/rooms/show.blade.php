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
                    <div class="border border-gray-400 rounded-lg p-6 mb-4 shadow-sm">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">
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

                            <div class="relative shrink-0" x-data="{ open: false }">
                                <button @click="open = !open" @click.outside="open = false"
                                    class="text-gray-400 hover:text-gray-700 px-2 text-xl leading-none">
                                    ⋮
                                </button>

                                <div x-show="open" x-cloak
                                    class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-10 text-sm p-3">

                                    <p class="text-xs text-gray-400 mb-2">Status</p>
                                    <div class="grid grid-cols-2 gap-1 mb-3">
                                        @foreach (['pendente' => 'Pendente', 'em_preparo' => 'Em preparo', 'pronto' => 'Pronto', 'entregue' => 'Entregue'] as $value => $label)
                                            <form method="POST" action="{{ route('orders.status', [$room, $order]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $value }}">
                                                <button type="submit"
                                                    class="w-full text-xs rounded px-2 py-1.5 {{ $order->status === $value ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                                    {{ $label }}
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>

                                    <div class="border-t border-gray-100 pt-2 space-y-1">
                                        <a href="{{ route('orders.edit', [$room, $order]) }}"
                                            class="block px-2 py-1.5 rounded hover:bg-gray-50 text-blue-600">
                                            Editar pedido
                                        </a>

                                        <form method="POST" action="{{ route('orders.destroy', [$room, $order]) }}"
                                            onsubmit="return confirm('Excluir este pedido? Essa ação não pode ser desfeita.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-full text-left px-2 py-1.5 rounded hover:bg-red-50 text-red-600">
                                                Excluir pedido
                                            </button>
                                        </form>

                                        <button @click="open = false"
                                            class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-50 text-gray-500">
                                            Fechar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Nenhum pedido ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
        
</x-app-layout>