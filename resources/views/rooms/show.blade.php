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

            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <div>
                        <p class="text-sm text-gray-500">
                            Código da sala
                            @if ($room->status === 'encerrada')
                                <span class="ml-2 inline-block text-xs font-semibold bg-gray-200 text-gray-700 rounded-full px-2 py-0.5">Encerrada</span>
                            @endif
                        </p>
                        <p class="text-2xl font-bold tracking-widest">{{ $room->codigo }}</p>
                        <p class="text-xs text-gray-400 mt-1">Aberta em {{ $room->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="relative self-start" x-data="{ aberto: false }" @click.outside="aberto = false">
                        <button @click="aberto = !aberto"
                            class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-full px-4 py-2 hover:bg-amber-100"
                            title="Estoque de pão — clique para ajustar">
                            <span class="text-xl leading-none">🥖</span>
                            <span class="font-bold text-amber-900">{{ $estoque }}</span>
                        </button>

                        <div x-show="aberto" x-cloak
                            class="absolute left-1/2 -translate-x-1/2 mt-2 w-72 max-w-[calc(100vw-2rem)] bg-white border border-gray-200 rounded-md shadow-lg z-10 p-4">
                            <p class="text-sm font-semibold text-gray-900 mb-1">Estoque de pão</p>
                            <p class="text-xs text-gray-500 mb-3">
                                Cada lanche em um pedido consome 1 pão.
                                Agora: <strong>{{ $estoque }}</strong> {{ $estoque === 1 ? 'pão' : 'pães' }}.
                            </p>

                            <form method="POST" action="{{ route('rooms.estoque', $room) }}">
                                @csrf
                                @method('PATCH')

                                @error('quantidade')
                                    <p class="text-xs text-red-600 mb-2">{{ $message }}</p>
                                @enderror

                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🥖</span>
                                    <input type="number" name="quantidade" min="0" max="100000"
                                        value="{{ $estoque > 0 ? $estoque : '' }}"
                                        placeholder="{{ $estoque > 0 ? $estoque : 'Ex: 120' }}"
                                        class="w-24 rounded-md border-gray-300 shadow-sm text-center">
                                    <button type="submit"
                                        class="bg-gray-900 text-white text-sm rounded-md px-3 py-1.5 hover:bg-gray-700">
                                        OK
                                    </button>
                                    <button type="button" @click="aberto = false"
                                        class="bg-gray-100 text-gray-700 text-sm rounded-md px-3 py-1.5 hover:bg-gray-200">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-2 sm:gap-3">
                    @if ($room->status === 'aberta')
                        <a href="{{ route('orders.create', $room) }}"
                            class="bg-gray-900 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-gray-700">
                            + Novo Pedido
                        </a>

                        <a href="{{ route('cozinha.index', $room) }}"
                            class="bg-gray-100 text-gray-800 rounded-md px-4 py-2 text-sm font-medium hover:bg-gray-200">
                            👨‍🍳 Ver cozinha
                        </a>
                    @endif

                    <a href="{{ route('rooms.relatorio', $room) }}?{{ now()->format('U') }}" target="_blank"
                        class="bg-gray-100 text-gray-800 rounded-md px-4 py-2 text-sm font-medium hover:bg-gray-200">
                        📊 Relatório (PDF)
                    </a>

                    @if ($room->criado_por === auth()->id() && $room->status === 'aberta')
                        <a href="{{ route('rooms.encerrar.form', $room) }}"
                            class="ml-auto text-sm text-red-600 hover:underline">
                            Encerrar sala
                        </a>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold text-gray-900 mb-4">Pedidos</h3>

                    @forelse ($room->orders as $order)
                    @php
                        $corCard = match($order->status) {
                            'pronto' => 'border-blue-600 bg-blue-100',
                            'entregue' => 'border-red-400 bg-red-50',
                            default => 'border-gray-200 bg-white',
                        };
                    @endphp
                    <div class="border {{ $corCard }} rounded-lg p-4 mb-3 shadow-sm">
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

                            <div class="relative shrink-0" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
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
            <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Echo.private('sala.{{ $room->id }}')
                .listen('.OrderCreated', () => window.location.reload())
                .listen('.OrderUpdated', () => window.location.reload())
                .listen('.OrderStatusUpdated', () => window.location.reload())
                .listen('.OrderDeleted', ()=> window.location.reload())
        });
    </script>
</x-app-layout>