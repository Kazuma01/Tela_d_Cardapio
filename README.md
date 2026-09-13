# Tela_d_Cardapio

> Textos deste README organizados com apoio de IA para facilitar o entendimento do projeto.

Sistema de comandas para restaurante feito em Laravel: o garçom cria uma **sala** de atendimento, registra os **pedidos** direto do cardápio, e a **cozinha** acompanha o que precisa ser preparado em uma tela dedicada.

## Funcionalidades

- ✅ Cardápio (categorias e produtos)
- ✅ Criar sala e entrar em sala existente (código + senha + papel: garçom ou cozinha)
- ✅ Criar e editar pedidos
- ✅ Tela da cozinha — pedidos pendentes/em preparo em cards, paginados de 5 em 5, com botão para marcar como pronto
- ⏳ Atualização em tempo real (Laravel Reverb + Echo) — em desenvolvimento

## Papéis dentro da sala

- **Garçom** — controle total: cria a sala, registra e edita pedidos, também pode acessar a tela da cozinha
- **Cozinha** — acompanha os pedidos pendentes e marca como prontos

## Tecnologias

- PHP 8.3 / Laravel 12
- Blade + Tailwind CSS + Alpine.js
- Vite
- MySQL 8.4
- Redis
- Docker (Nginx + PHP-FPM + MySQL + phpMyAdmin + Redis)

## Passo a passo para rodar o projeto

Clone o projeto

```sh
git clone https://github.com/Kazuma01/Tela_d_Cardapio.git
cd Tela_d_Cardapio/
```

Crie o arquivo `.env`

```sh
cp .env.example .env
```

Atualize essas variáveis de ambiente no arquivo `.env` (os nomes dos serviços seguem o `docker-compose.yml` do projeto):

```dosini
APP_NAME="Tela do Cardápio"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=nome_que_desejar_db
DB_USERNAME=nome_usuario
DB_PASSWORD=senha_aqui

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Suba os containers do projeto

```sh
docker-compose up -d
```

Acesse o container da aplicação

```sh
docker-compose exec app bash
```

Instale as dependências PHP

```sh
composer install
```

Gere a key do projeto Laravel

```sh
php artisan key:generate
```

Rode as migrations (e os seeders, para já ter categorias/produtos de exemplo)

```sh
php artisan migrate --seed
```

Em outro terminal, na raiz do projeto (fora do container), instale as dependências JS e suba o Vite

```sh
npm install
npm run dev
```

Acesse o projeto

- App: [http://localhost:8000](http://localhost:8000)
- phpMyAdmin: [http://localhost:8080](http://localhost:8080)

## Rodando os testes

```sh
php artisan test
```
