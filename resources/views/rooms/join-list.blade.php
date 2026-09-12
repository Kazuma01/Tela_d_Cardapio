<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Salas Abertas</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                @forelse ($salas as $sala)
                    <a href="{{ route('rooms.join.form', $sala) }}"
                        class="flex justify-between items-center p-4 hover:bg-gray-50">
                        <span class="font-medium text-gray-800">{{ $sala->nome }}</span>
                        <span class="text-xs text-gray-400">{{ $sala->codigo }}</span>
                    </a>
                @empty
                    <p class="p-4 text-sm text-gray-500">Nenhuma sala aberta no momento.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>