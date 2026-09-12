<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Criar Sala</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('rooms.store') }}">
                    @csrf

                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome da sala</label>
                    <input type="text" name="nome" required maxlength="100" value="{{ old('nome') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm mb-3">
                    @error('nome')
                        <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                    @enderror

                    <label class="block text-sm font-medium text-gray-700 mb-1">Defina uma senha para a sala</label>
                    <input type="password" name="senha" required minlength="4"
                        class="w-full rounded-md border-gray-300 shadow-sm mb-2">
                    @error('senha')
                        <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                        class="w-full bg-gray-900 text-black border border-gray-300 rounded-md py-2 mt-2 hover:bg-gray-700">
                        Criar sala
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>