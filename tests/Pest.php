<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function criarSalaComGarcom(): array
{
    $garcom = User::factory()->create();

    $room = Room::create([
        'nome' => 'Sala Teste',
        'codigo' => strtoupper(Str::random(5)),
        'senha' => Hash::make('senha123'),
        'criado_por' => $garcom->id,
        'status' => 'aberta',
    ]);

    $room->participantes()->attach($garcom->id, ['papel' => 'garcom']);

    return [$garcom, $room];
}

function criarProduto(string $nome, float $preco = 10.0, bool $disponivel = true): Product
{
    $categoria = Category::firstOrCreate(['nome' => 'Lanches']);

    return Product::create([
        'category_id' => $categoria->id,
        'nome' => $nome,
        'preco' => $preco,
        'disponivel' => $disponivel,
    ]);
}
