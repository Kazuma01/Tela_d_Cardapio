<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cardápio
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @foreach ($categorias as $categoria)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $categoria->nome }}</h3>

                    <div class="grid gap-3">
                        @foreach ($categoria->products as $produto)
                            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                                <span class="text-gray-800">{{ $produto->nome }}</span>
                                <span class="font-semibold text-gray-900">
                                    R$ {{ number_format($produto->preco, 2, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>