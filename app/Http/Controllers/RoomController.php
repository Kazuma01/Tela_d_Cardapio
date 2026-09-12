<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function create(): View
    {
        return view('rooms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'senha' => ['required', 'string', 'min:4'],
        ]);

        $room = Room::create([
            'nome' => $request->nome,
            'codigo' => $this->gerarCodigoUnico(),
            'senha' => Hash::make($request->senha),
            'criado_por' => Auth::id(),
            'status' => 'aberta',
        ]);

        $room->participantes()->attach(Auth::id(), ['papel' => 'garcom']);

        return redirect()
            ->route('rooms.show', $room)
            ->with('status', 'Sala criada com sucesso!');
    }

    public function show(Room $room): View
    {
        $room->load(['orders' => function ($query) {
            $query->with('items.product')->latest();
        }]);

        return view('rooms.show', compact('room'));
    }

    public function joinList(): View
    {
        $salas = Room::where('status', 'aberta')->latest()->get();

        return view('rooms.join-list', compact('salas'));
    }

    public function joinForm(Room $room): View
    {
        abort_if($room->status !== 'aberta', 404);

        return view('rooms.join', compact('room'));
    }

    public function join(Request $request, Room $room): RedirectResponse
    {
        abort_if($room->status !== 'aberta', 404);

        $request->validate([
            'senha' => ['required', 'string'],
            'papel' => ['required', 'in:garcom,cozinha'],
        ]);

        if (! Hash::check($request->senha, $room->senha)) {
            return back()->withErrors(['senha' => 'Senha incorreta.']);
        }

        $room->participantes()->syncWithoutDetaching([
            Auth::id() => ['papel' => $request->papel],
        ]);

        return redirect()
            ->route('rooms.show', $room)
            ->with('status', "Você entrou na sala como {$request->papel}.");
    }

    private function gerarCodigoUnico(): string
    {
        do {
            $codigo = strtoupper(Str::random(5));
        } while (Room::where('codigo', $codigo)->exists());

        return $codigo;
    }
}