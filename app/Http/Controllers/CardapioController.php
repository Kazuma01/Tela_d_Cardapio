<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class CardapioController extends Controller
{
    public function index(): View
    {
        $categorias = Category::with(['products' => function ($query) {
            $query->where('disponivel', true)->orderBy('preco');
        }])->get();

        return view('cardapio.index', compact('categorias'));
    }
}