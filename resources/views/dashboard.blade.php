<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('cardapio.index') }}"
                    class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <p class="font-semibold text-gray-900"> Cardápio</p>
                    <p class="text-sm text-gray-500 mt-1">Ver categorias e produtos</p>
                </a>

                <a href="{{ route('rooms.create') }}"
                    class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <p class="font-semibold text-gray-900"> Criar Sala</p>
                    <p class="text-sm text-gray-500 mt-1">Abrir um novo atendimento</p>
                </a>

                <a href="{{ route('rooms.join.list') }}"
                    class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <p class="font-semibold text-gray-900"> Entrar em Sala</p>
                    <p class="text-sm text-gray-500 mt-1">Como garçom ou cozinha</p>
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-2">Progresso do projeto</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ Cardápio (categorias e produtos)</li>
                    <li>✅ Criar e entrar em sala (código + senha + papel)</li>
                    <li>✅ Criar e editar pedido</li>
                    <li>⏳ Tela dedicada da cozinha (cards de 5 em 5)</li>
                    <li>⏳ Tempo real (Reverb + Echo)</li>
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>