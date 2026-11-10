<?php

namespace App\Http\Controllers;

use App\Models\BreadStock;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(Room $room): View
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode criar pedidos.');
        abort_if($room->status !== 'aberta', 403, 'A sala está encerrada e não aceita novos pedidos.');

        $categorias = Category::with(['products' => function ($query) {
            $query->where('disponivel', true)->orderBy('preco');
        }])->get();

        $estoque = BreadStock::atual()->quantidade;

        return view('orders.create', compact('room', 'categorias', 'estoque'));
    }

    public function store(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode criar pedidos.');
        abort_if($room->status !== 'aberta', 403, 'A sala está encerrada e não aceita novos pedidos.');

        $data = $request->validate([
            'identificacao' => ['nullable', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:500'],
            'itens' => ['required', 'array'],
            'itens.*.quantidade' => ['nullable', 'integer', 'min:0'],
            'itens.*.observacao' => ['nullable', 'string', 'max:255'],
        ]);

        $itensValidos = collect($data['itens'])->filter(fn ($item) => ($item['quantidade'] ?? 0) > 0);

        if ($itensValidos->isEmpty()) {
            return back()->withErrors(['itens' => 'O pedido precisa ter pelo menos um item.'])->withInput();
        }

        $produtos = Product::whereIn('id', $itensValidos->keys())->where('disponivel', true)->get();

        if ($produtos->count() !== $itensValidos->count()) {
            return back()->withErrors(['itens' => 'Um dos produtos escolhidos não está mais disponível no cardápio.'])->withInput();
        }

        // Contador de pão: cada unidade de lanche consome exatamente 1 pão
        $totalPaes = (int) $itensValidos->sum('quantidade');
        $estoque = BreadStock::atual();

        if ($estoque->quantidade <= 0) {
            return back()->withErrors(['itens' => 'Estoque de pão esgotado — reponha o contador antes de criar novos pedidos.'])->withInput();
        }

        if ($totalPaes > $estoque->quantidade) {
            return back()->withErrors(['itens' => "Não há pão suficiente: o pedido precisa de {$totalPaes} pães e o estoque tem apenas {$estoque->quantidade}. Ajuste as quantidades."])->withInput();
        }

        $order = Order::create([
            'room_id' => $room->id,
            'criado_por' => Auth::id(),
            'identificacao' => $data['identificacao'] ?? null,
            'observacao' => $data['observacao'] ?? null,
            'status' => 'pendente',
        ]);

        foreach ($itensValidos as $productId => $item) {
            $produto = $produtos->firstWhere('id', (int) $productId);

            $order->items()->create([
                'product_id' => $produto->id,
                'quantidade' => $item['quantidade'],
                'preco_unitario' => $produto->preco,
                'observacao' => $item['observacao'] ?? null,
            ]);
        }

        $estoque->consumir($totalPaes);

        event(new \App\Events\OrderCreated($order));

        return redirect()
            ->route('rooms.show', $room)
            ->with('status', 'Pedido enviado com sucesso!');
    }

    public function edit(Room $room, Order $order): View
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode editar pedidos.');
        abort_if($room->status !== 'aberta', 403, 'A sala está encerrada e não aceita alterações em pedidos.');

        $order->load('items.product');

        $categorias = Category::with(['products' => function ($query) {
            $query->where('disponivel', true)->orderBy('preco');
        }])->get();

        $estoque = BreadStock::atual()->quantidade;

        return view('orders.edit', compact('room', 'order', 'categorias', 'estoque'));
    }

    public function update(Request $request, Room $room, Order $order): RedirectResponse
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode editar pedidos.');
        abort_if($room->status !== 'aberta', 403, 'A sala está encerrada e não aceita alterações em pedidos.');

        $data = $request->validate([
            'identificacao' => ['nullable', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:500'],
            'itens' => ['required', 'array'],
            'itens.*.quantidade' => ['nullable', 'integer', 'min:0'],
            'itens.*.observacao' => ['nullable', 'string', 'max:255'],
        ]);

        $itensValidos = collect($data['itens'])->filter(fn ($item) => ($item['quantidade'] ?? 0) > 0);

        if ($itensValidos->isEmpty()) {
            return back()->withErrors(['itens' => 'O pedido precisa ter pelo menos um item.'])->withInput();
        }

        $produtos = Product::whereIn('id', $itensValidos->keys())->where('disponivel', true)->get();

        if ($produtos->count() !== $itensValidos->count()) {
            return back()->withErrors(['itens' => 'Um dos produtos escolhidos não está mais disponível no cardápio.'])->withInput();
        }

        // Contador de pão: ajusta o estoque pela diferença entre o pedido antigo e o novo
        $totalAntigo = (int) $order->items()->sum('quantidade');
        $totalNovo = (int) $itensValidos->sum('quantidade');
        $diferenca = $totalNovo - $totalAntigo;
        $estoque = BreadStock::atual();

        if ($diferenca > 0 && $diferenca > $estoque->quantidade) {
            return back()->withErrors(['itens' => "Não há pão suficiente: a alteração precisa de mais {$diferenca} pães e o estoque tem apenas {$estoque->quantidade}. Ajuste as quantidades."])->withInput();
        }

        $order->update([
            'identificacao' => $data['identificacao'] ?? null,
            'observacao' => $data['observacao'] ?? null,
        ]);

        $order->items()->delete();

        foreach ($itensValidos as $productId => $item) {
            $produto = $produtos->firstWhere('id', (int) $productId);

            $order->items()->create([
                'product_id' => $produto->id,
                'quantidade' => $item['quantidade'],
                'preco_unitario' => $produto->preco,
                'observacao' => $item['observacao'] ?? null,
            ]);
        }

        if ($diferenca > 0) {
            $estoque->consumir($diferenca);
        } elseif ($diferenca < 0) {
            $estoque->repor(abs($diferenca));
        }

        event(new \App\Events\OrderUpdated($order->fresh()));

        return redirect()
            ->route('rooms.show', $room)
            ->with('status', 'Pedido atualizado com sucesso!');

    }
    
    public function updateStatus(Request $request, Room $room, Order $order): RedirectResponse
{
    abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode alterar o status.');

    $data = $request->validate([
        'status' => ['required', 'in:pendente,em_preparo,pronto,entregue'],
    ]);

    $order->update(['status' => $data['status']]);

    event(new \App\Events\OrderStatusUpdated($order));

    return redirect()->route('rooms.show', $room)->with('status', 'Status atualizado.');
}
    public function destroy(Room $room, Order $order): RedirectResponse
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode excluir pedidos.');

        // Devolve ao estoque os pães que este pedido estava consumindo
        $totalPaes = (int) $order->items()->sum('quantidade');
        $orderId = $order->id;
        $order->delete();

        if ($totalPaes > 0) {
            BreadStock::atual()->repor($totalPaes);
        }

        event(new \App\Events\OrderDeleted($orderId, $room->id));

        return redirect()
        ->route('rooms.show', $room)
        ->with('status', 'Pedido excluído.');
    }

}