<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function index(Room $room): View
{
    abort_unless($room->isParticipante(Auth::id()), 403, 'Você não faz parte desta sala.');

    $papel = $room->papelDe(Auth::id());

    $orders = $room->orders()
    ->whereIn('status', ['pendente', 'em_preparo'])
        ->with('items.product')
        ->oldest()
        ->paginate(3);

    return view('cozinha.index', compact('room', 'orders', 'papel'));
}
    public function markReady(Room $room, Order $order): RedirectResponse
    {
        abort_unless($room->isParticipante(Auth::id()), 403, 'Você não faz parte desta sala.');

        $order->update(['status' => 'pronto']);

        return back()->with('status', 'Pedido marcado como pronto!');
    }
}