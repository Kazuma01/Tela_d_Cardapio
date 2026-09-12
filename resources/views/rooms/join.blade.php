<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Entrar em "{{ $room->nome }}"</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @error('senha')
                    <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('rooms.join', $room) }}">
                    @csrf

                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" name="senha" required
                        class="w-full rounded-md border-gray-300 shadow-sm mb-3">

                    <label class="block text-sm font-medium text-gray-700 mb-1">Entrar como</label>
                    <select name="papel" required class="w-full rounded-md border-gray-300 shadow-sm mb-4">
                        <option value="garcom">Garçom</option>
                        <option value="cozinha">Cozinha</option>
                    </select>

                    <button type="submit"
                        class="w-full bg-gray-900 text-black border border-gray-300 rounded-md py-2 hover:bg-gray-700">
                        Entrar
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>