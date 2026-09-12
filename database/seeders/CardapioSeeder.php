<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CardapioSeeder extends Seeder
{
    public function run(): void{
        $categoria = Category::firstOrCreate(['nome' => 'Lanches']);

        $produtos = [
            ['nome' => 'Tradicional P',        'preco' => 5.00],
            ['nome' => 'Misto',                 'preco' => 6.00],
            ['nome' => 'Tradicional G',         'preco' => 7.00],
            ['nome' => 'X-Tudo sem ovo',        'preco' => 9.00],
            ['nome' => 'X-Tudo com ovo',        'preco' => 10.00],
            ['nome' => 'Hambúrguer Artesanal',  'preco' => 15.00],
        ];

        foreach ($produtos as $produto) {
            Product::firstOrCreate(
                ['nome' => $produto['nome'], 'category_id' => $categoria->id],
                ['preco' => $produto['preco'], 'disponivel' => true]
            );
        }
    }
}