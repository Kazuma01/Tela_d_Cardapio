<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class KitchenLayout extends Component
{
    /**
     * Layout dedicado da cozinha: sem navbar, fundo escuro, tela cheia.
     */
    public function render(): View
    {
        return view('layouts.kitchen');
    }
}
