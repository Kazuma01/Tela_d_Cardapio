<x-guest-layout>
    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name', 'Tela de Cardápio') }}</h1>
        <p class="text-sm text-gray-600 mt-1 mb-6">
            Cardápio e pedidos em tempo real — garçom e cozinha na mesma página.
        </p>
    </div>

    <div class="space-y-3">
        <a href="{{ route('cardapio.index') }}"
            class="block w-full bg-gray-900 text-white text-center rounded-md py-2.5 hover:bg-gray-700">
            📋 Ver Cardápio
        </a>

        @guest
            <a href="{{ route('login') }}"
                class="block w-full bg-gray-100 text-gray-800 text-center rounded-md py-2.5 hover:bg-gray-200">
                Entrar
            </a>

            <a href="{{ route('register') }}"
                class="block w-full text-sm text-blue-600 hover:underline text-center py-1">
                Criar uma conta
            </a>
        @else
            <a href="{{ route('dashboard') }}"
                class="block w-full bg-gray-100 text-gray-800 text-center rounded-md py-2.5 hover:bg-gray-200">
                Ir para o Dashboard
            </a>
        @endguest
    </div>
</x-guest-layout>
