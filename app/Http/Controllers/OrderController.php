<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
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

        $categorias = Category::with(['products' => function ($query) {
            $query->where('disponivel', true)->orderBy('preco');
        }])->get();

        return view('orders.create', compact('room', 'categorias'));
    }

    public function store(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode criar pedidos.');

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

        $order = Order::create([
            'room_id' => $room->id,
            'criado_por' => Auth::id(),
            'identificacao' => $data['identificacao'] ?? null,
            'observacao' => $data['observacao'] ?? null,
            'status' => 'pendente',
        ]);

        foreach ($itensValidos as $productId => $item) {
            $produto = \App\Models\Product::findOrFail($productId);

            $order->items()->create([
                'product_id' => $produto->id,
                'quantidade' => $item['quantidade'],
                'preco_unitario' => $produto->preco,
                'observacao' => $item['observacao'] ?? null,
            ]);
        }
        event(new \App\Events\OrderCreated($order));

        return redirect()
            ->route('rooms.show', $room)
            ->with('status', 'Pedido enviado com sucesso!');
    }

    public function edit(Room $room, Order $order): View
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode editar pedidos.');

        $order->load('items.product');

        $categorias = Category::with(['products' => function ($query) {
            $query->where('disponivel', true)->orderBy('preco');
        }])->get();

        return view('orders.edit', compact('room', 'order', 'categorias'));
    }

    public function update(Request $request, Room $room, Order $order): RedirectResponse
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode editar pedidos.');

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

        $order->update([
            'identificacao' => $data['identificacao'] ?? null,
            'observacao' => $data['observacao'] ?? null,
        ]);

        $order->items()->delete();

        foreach ($itensValidos as $productId => $item) {
            $produto = \App\Models\Product::findOrFail($productId);

            $order->items()->create([
                'product_id' => $produto->id,
                'quantidade' => $item['quantidade'],
                'preco_unitario' => $produto->preco,
                'observacao' => $item['observacao'] ?? null,
            ]);
        }

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
            
            return redirect()->route('rooms.show', $room)->with('status', 'Status atualizado.');
    }
    public function destroy(Room $room, Order $order): RedirectResponse
    {
        abort_unless($room->isGarcom(Auth::id()), 403, 'Apenas o garçom pode excluir pedidos.');
        
        $order->delete();
        
        return redirect()
        ->route('rooms.show', $room)
        ->with('status', 'Pedido excluído.');
    }

}