<?php

namespace App\Http\Controllers;

use App\Models\BreadStock;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
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
    if ($room->papelDe(Auth::id()) === 'cozinha') {
        return redirect()->route('cozinha.index', $room);
    }

    $room->load(['orders' => function ($query) {
        $query->with('items.product')->latest();
    }]);

    $estoque = BreadStock::atual()->quantidade;

    return view('rooms.show', compact('room', 'estoque'));
}

    public function encerrarForm(Room $room): View
    {
        abort_unless($room->criado_por === Auth::id(), 403, 'Apenas quem criou a sala pode encerrá-la.');

        $room->loadCount(['orders as pedidos_abertos' => function ($query) {
            $query->whereNotIn('status', ['entregue']);
        }]);

        return view('rooms.encerrar', compact('room'));
    }

    public function encerrar(Room $room): RedirectResponse
    {
        abort_unless($room->criado_por === Auth::id(), 403, 'Apenas quem criou a sala pode encerrá-la.');
        abort_if($room->status !== 'aberta', 404);

        $room->update(['status' => 'encerrada']);

        return redirect()
            ->route('rooms.join.list')
            ->with('status', 'Sala encerrada com sucesso!');
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

    if ($request->papel === 'cozinha') {
        return redirect()
            ->route('cozinha.index', $room)
            ->with('status', 'Você entrou na sala como cozinha.');
    }

    return redirect()
        ->route('rooms.show', $room)
        ->with('status', 'Você entrou na sala como garçom.');
    }

    public function atualizarEstoque(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode ajustar o estoque de pão.');

        $data = $request->validate([
            'quantidade' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        BreadStock::atual()->definir($data['quantidade']);

        return back()->with('status', 'Estoque de pão atualizado para ' . $data['quantidade'] . ' pães.');
    }

    public function relatorioVendas(Room $room): Response
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode ver o relatório de vendas.');

        // Conta todo pedido registrado na sala; pedidos excluídos saem do histórico
        $linhas = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.room_id', $room->id)
            ->groupBy('products.id', 'products.nome')
            ->selectRaw('products.nome as produto, SUM(order_items.quantidade) as quantidade, SUM(order_items.quantidade * order_items.preco_unitario) as total')
            ->get()
            ->sortByDesc('total')
            ->values();

        $totalGeral = $linhas->sum('total');
        $totalItens = $linhas->sum('quantidade');
        $totalPedidos = $room->orders()->count();

        $pdf = Pdf::loadView('relatorios.vendas', compact('room', 'linhas', 'totalGeral', 'totalItens', 'totalPedidos'));

        // Sempre gera um PDF novo com os dados atuais — navegador não pode servir versão em cache
        return $pdf->stream('relatorio-vendas-' . $room->codigo . '.pdf')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    private function gerarCodigoUnico(): string
    {
        do {
            $codigo = strtoupper(Str::random(5));
        } while (Room::where('codigo', $codigo)->exists());

        return $codigo;
    }
}