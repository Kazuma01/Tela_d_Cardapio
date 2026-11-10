<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Encerrar Sala {{ $room->nome }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Tem certeza que deseja encerrar esta sala?</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Sala <span class="font-semibold">{{ $room->nome }}</span> (código
                    <span class="font-mono tracking-widest">{{ $room->codigo }}</span>),
                    aberta em {{ $room->created_at->format('d/m/Y H:i') }}.
                </p>

                <div class="border border-red-200 bg-red-50 rounded-md p-4 mb-6">
                    <p class="text-sm text-red-700">
                        ⚠️ Depois de encerrada, a sala não aceita mais entrada de novos participantes nem novos pedidos.
                        @if ($room->pedidos_abertos > 0)
                            Esta sala ainda tem <strong>{{ $room->pedidos_abertos }}</strong>
                            {{ $room->pedidos_abertos === 1 ? 'pedido não entregue' : 'pedidos não entregues' }} —
                            confira o painel antes de encerrar.
                        @else
                            Todos os pedidos desta sala já foram entregues.
                        @endif
                    </p>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('rooms.show', $room) }}"
                        class="flex-1 text-center bg-gray-100 text-gray-700 rounded-md py-2 hover:bg-gray-200">
                        Cancelar
                    </a>

                    <form method="POST" action="{{ route('rooms.encerrar', $room) }}" class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="w-full bg-red-600 text-white rounded-md py-2 hover:bg-red-700">
                            Encerrar sala
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
