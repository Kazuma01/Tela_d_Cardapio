# Tela_d_Cardapio

> Textos deste README organizados com apoio de IA para facilitar o entendimento do projeto.

Sistema de comandas para restaurante feito em Laravel: o garçom cria uma **sala** de atendimento, registra os **pedidos** direto do cardápio, e a **cozinha** acompanha o que precisa ser preparado em uma tela dedicada. Tudo se atualiza **em tempo real** entre os aparelhos.

Projeto **100% offline**: roda inteiro na rede local (LAN), sem CDN, fonte ou recurso externo.

## Funcionalidades

- ✅ Cardápio (categorias e produtos)
- ✅ Criar sala e entrar em sala existente (código + senha + papel: garçom ou cozinha)
- ✅ Criar, editar e excluir pedidos
- ✅ Tela da cozinha — pedidos pendentes/em preparo em cards, paginados de 5 em 5, com botão para marcar como pronto
- ✅ Tempo real (Laravel Reverb + Echo) — pedido criado/editado/pronto aparece sozinho nos outros aparelhos, sem recarregar
- ✅ Estoque global de pão (cada lanche consome 1 pão, baixa atômica)
- ✅ Relatório de vendas em PDF
- ✅ HTTPS/TLS com certificado local (Caddy) e auditoria de eventos de segurança
- ✅ Encerrar sessões remotamente (“Sair de todos os dispositivos”)

## Papéis dentro da sala

- **Garçom** — controle total: cria a sala, registra e edita pedidos, também pode acessar a tela da cozinha
- **Cozinha** — acompanha os pedidos pendentes e marca como prontos

## Tecnologias

- PHP 8.4 / Laravel 12
- Blade + Tailwind CSS + Alpine.js
- Vite
- MySQL 8.4
- Redis
- Docker (Caddy + Nginx + PHP-FPM + MySQL + phpMyAdmin + Redis + Reverb)

O **Caddy** é a única porta pública (80/443): ele emite o certificado TLS local, redireciona HTTP → HTTPS e encaminha o WebSocket do Reverb. Os demais serviços não ficam expostos na rede.

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

Ajuste no `.env` as variáveis principais (os nomes dos serviços seguem o `docker-compose.yml`):

```dosini
APP_NAME="Tela do Cardápio"
APP_URL=https://localhost

# IP atual da máquina na rede local — o Caddy inclui esse IP no certificado.
# Se o IP mudar, atualize aqui e recrie o Caddy (ou use os nomes mDNS).
CARDAPIO_IP=192.168.100.114

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

> O `.env.example` já vem com os valores de sessão segura, broadcast e Reverb usados pelo projeto. Em caso de dúvida, o guia completo de HTTPS/rede está em `docs/HTTPS.md`.

Suba os containers

```sh
docker compose up -d
```

Instale as dependências PHP (dentro do container)

```sh
docker compose exec app bash
composer install
```

Gere a chave do Laravel (ainda no container)

```sh
php artisan key:generate
```

Rode as migrations (e os seeders, para já ter categorias/produtos de exemplo)

```sh
php artisan migrate --seed
```

> Em caso de 502, rode `docker compose restart nginx` — o nginx pode ter guardado o IP antigo do container `app`.

Em outro terminal, na raiz do projeto (fora do container), instale as dependências JS e gere o build

```sh
npm install
npm run dev    # desenvolvimento (com hot-reload)
# ou
npm run build  # produção (arquivos finais em public/build)
```

## Como acessar

| Serviço | Endereço |
|---|---|
| App | `https://localhost` · `https://cardapio.local` · `https://kazuarch.local` · `https://<CARDAPIO_IP>` |
| phpMyAdmin | `http://127.0.0.1:8080` (somente na máquina do servidor) |
| MySQL | `127.0.0.1:3300` (somente na máquina do servidor) |

### Primeiro acesso em um dispositivo (certificado)

O certificado é emitido por uma **CA local** (do Caddy). Na primeira vez, o navegador mostra um aviso de segurança — aceite para entrar, ou instale a CA de uma vez:

1. No dispositivo, abra `http://<CARDAPIO_IP>/cardapio-ca-raiz.crt` (HTTP, sem aviso de certificado) e baixe o arquivo.
2. Instale como **certificado de CA** (no Android: Configurações → Segurança → Instalar certificado → CA).
3. No Android, feche o Chrome em **Configurações → Apps → Chrome → Forçar parada** antes de reabrir.

Depois disso o site fica com cadeado e o tempo real (WSS) funciona sem avisos. Guia detalhado: `docs/HTTPS.md`.

## Rodando os testes

O PHP roda no container, então:

```sh
docker compose exec -T app php artisan test
```

Os testes usam SQLite em memória — nunca apontam para o MySQL de desenvolvimento.

## Solução de problemas

Os tropeços mais comuns no dia a dia do projeto (e como resolver):

### 502 Bad Gateway

O nginx guarda o IP interno do container `app`. Se o `app` foi recriado (`docker compose up -d`, rebuild...), o nginx ainda aponta para o IP antigo:

```sh
docker compose restart nginx
```

### Tempo real parou de atualizar

A tela não recebe mais pedido novo/status sem recarregar. Verifique nesta ordem:

1. **Cadeado no navegador** — se o site mostra aviso de certificado, o WebSocket não conecta (o navegador bloqueia `wss://`). Instale a CA (seção de primeiro acesso acima).
2. **Android/Chrome** — depois de instalar a CA, feche o Chrome em *Configurações → Apps → Chrome → Forçar parada*. Fechar só a aba não basta.
3. **Recarregar forte** — `Ctrl+Shift+R` no PC (o `F5` pode reaproveitar o JavaScript antigo da aba).
4. **Trocar de endereço** — se o IP do servidor mudou, o certificado não cobre mais o IP; acesse por `https://cardapio.local` ou `https://kazuarch.local`.

### Site sem cadeado em um dispositivo

Instale a CA: baixe `http://<IP>/cardapio-ca-raiz.crt` e instale como *certificado de CA* (detalhes na seção de primeiro acesso). Se a CA do servidor mudar, todos os dispositivos precisam instalar de novo.

### IP do servidor mudou

O certificado cobre `cardapio.local`, `kazuarch.local`, `localhost` e o IP em `CARDAPIO_IP`. Para atualizar o IP no certificado, ajuste `CARDAPIO_IP` no `.env` e recrie o Caddy:

```sh
docker compose up -d --force-recreate caddy
```

Na rede local, prefira sempre os nomes mDNS (`cardapio.local`), que continuam válidos mesmo quando o IP muda.

## Dicas do dia a dia

### Atualizar o projeto depois de baixar mudanças

```sh
git pull
docker compose exec -T app composer install          # dependências PHP
docker compose exec -T app php artisan migrate       # banco
npm install && npm run build                         # JavaScript/CSS
docker compose restart nginx                         # evita 502
```

### Padrão de commits do projeto

Os commits seguem o formato `TC V<major>.<minor>.<patch> <descrição>`, por exemplo:

```sh
git commit -m "TC V0.8.1 tempo real: correção do transporte do Echo (ws + forceTLS) e download público da CA"
```

### Sessões e dispositivos

- Uma **mesma conta** pode ser usada em vários aparelhos sem problema — o tempo real não depende disso.
- **“Sair de todos os dispositivos”** (menu do usuário) encerra a sessão em todos os aparelhos de uma vez.
- Trocar de senha também desloga as outras sessões automaticamente (proteção padrão do Laravel).

## Estrutura essencial

| Caminho | O que contém |
|---|---|
| `routes/web.php` | Salas, pedidos, cozinha, estoque, relatório e dashboard |
| `app/Http/Controllers/` | Lógica de salas, pedidos, cozinha, cardápio e perfil |
| `app/Events/` | Eventos de tempo real (pedido criado/editado/status/excluído) |
| `resources/views/layouts/` | Layouts do sistema, da tela de login e da cozinha |
| `docker/caddy/Caddyfile` | TLS, redirecionamento e proxy (única porta pública) |
| `docs/HTTPS.md` | Guia de HTTPS, rede e instalação do certificado |
