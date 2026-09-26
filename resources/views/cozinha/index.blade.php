<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cozinha — {{ $room->nome }}</h2>

            @if ($papel === 'garcom')
                <a href="{{ route('rooms.show', $room) }}" class="text-sm text-blue-600 hover:underline">
                    ← Voltar ao painel da sala
                </a>
            @else
                <a href="{{ route('rooms.join.list') }}" class="text-sm text-blue-600 hover:underline">
                    ← Sair (voltar para lista de salas)
                </a>
            @endif
        </div>
    </x-slot>

    @php
        $nextUrl = $orders->nextPageUrl();
        $prevUrl = $orders->previousPageUrl();
    @endphp

    <div class="pb-28"
        x-data="{ selected: null }"
        @keydown.window="
            if ($event.key === '1') selected = 0;
            if ($event.key === '2') selected = 1;
            if ($event.key === '3') selected = 2;
            if ($event.key === 'Enter' && selected !== null) {
                let form = document.getElementById('confirmar-' + selected);
                if (form) form.submit();
            }
            if ($event.key === 'ArrowRight') {
                @if ($nextUrl) window.location.href = '{{ $nextUrl }}'; @endif
            }
            if ($event.key === 'ArrowLeft') {
                @if ($prevUrl) window.location.href = '{{ $prevUrl }}'; @endif
            }
        ">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 pt-8 space-y-6">

            @if (session('status'))
                <div class="text-base text-green-700 bg-green-50 rounded-md p-4 font-medium">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @forelse ($orders as $order)
                    @php $idx = $loop->index; @endphp

                    <div
                        @click="selected = {{ $idx }}"
                        :class="selected === {{ $idx }} ? 'ring-[6px] ring-green-500' : 'ring-2 ring-gray-200'"
                        class="relative bg-white rounded-2xl shadow-md p-8 cursor-pointer transition min-h-[320px]">

                        <span class="absolute top-4 right-4 bg-gray-900 text-white text-2xl font-bold w-14 h-14 rounded-full flex items-center justify-center">
                            {{ $idx + 1 }}
                        </span>

                        <p class="font-bold text-2xl text-gray-900 mb-4 pr-14">
                            {{ $order->identificacao ?: 'Sem identificação' }}
                        </p>

                        <ul class="text-xl text-gray-800 space-y-2">
                            @foreach ($order->items as $item)
                                <li>
                                    <span class="font-bold">{{ $item->quantidade }}x</span> {{ $item->product->nome }}
                                    @if ($item->observacao)
                                        <br><span class="text-gray-500 text-base">Obs: {{ $item->observacao }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if ($order->observacao)
                            <p class="text-base text-gray-500 mt-4">Obs. geral: {{ $order->observacao }}</p>
                        @endif
                    </div>

                    <form id="confirmar-{{ $idx }}" method="POST"
                        action="{{ route('cozinha.pronto', [$room, $order]) }}" class="hidden">
                        @csrf
                    </form>
                @empty
                    <p class="text-2xl text-gray-500 col-span-3 text-center py-12">Nenhum pedido pendente 🎉</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Barra fixa de instruções -->
    <div class="fixed bottom-0 left-0 right-0 bg-gray-900 text-white py-4 shadow-lg">
        <div class="max-w-6xl mx-auto px-6 flex items-center justify-between text-lg font-medium">
            <span>
                Aperte <span class="bg-white text-gray-900 rounded px-2 mx-1">1</span>
                <span class="bg-white text-gray-900 rounded px-2 mx-1">2</span>
                <span class="bg-white text-gray-900 rounded px-2 mx-1">3</span>
                para selecionar
            </span>
            <span>
                <span class="bg-white text-gray-900 rounded px-2 mx-1">Enter</span> confirma como pronto
            </span>
            <span>
                @if ($prevUrl) <span class="bg-white text-gray-900 rounded px-2 mx-1">←</span> @endif
                @if ($nextUrl) <span class="bg-white text-gray-900 rounded px-2 mx-1">→</span> @endif
                troca página
            </span>
        </div>
    </div>
            <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Echo.private('sala.{{ $room->id }}')
                .listen('.OrderCreated', () => window.location.reload())
                .listen('.OrderStatusUpdated', () => window.location.reload());
        });
    </script>
</x-app-layout>