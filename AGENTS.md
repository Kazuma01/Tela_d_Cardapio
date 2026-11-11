# AGENTS.md — Instruções permanentes para agentes de IA

> **Leia este arquivo inteiro antes de qualquer ação neste repositório.**
> Idioma de comunicação com o usuário: **português do Brasil (PT-BR)**.
> O usuário está aprendendo programação: explique decisões importantes em linguagem simples, sem transformar toda resposta em um texto longo.

---

## 0. Contexto do projeto

**Tela_d_Cardapio** é um sistema de comandas para restaurante, feito em **Laravel 12**:

- O **garçom** cria uma **sala** (nome + senha, gera código), registra **pedidos** a partir do **cardápio** e acompanha o status.
- A **cozinha** acompanha os pedidos pendentes/em preparo em uma tela dedicada e marca como pronto.
- Tempo real com **Laravel Reverb + Echo** (canal privado por sala).
- **Estoque global de pão** (cada lanche consome 1 pão) e **relatório de vendas em PDF**.
- Projeto **100% offline**: não usar fontes, imagens ou scripts de CDN/sites externos.

**Stack real:** PHP 8.4.26 no container (composer exige `^8.2`), Laravel 12.70.1, Blade + Alpine.js + Tailwind via Vite, MySQL 8.4, Redis, Reverb, dompdf, Pest 4.

**Documento de arquitetura do usuário:** `/home/kazuma/Downloads/arquitetura-cardapio.docx` (fora do repositório). É o **backup de contexto para IA** e deve ser mantido em sincronia com o código. Não commitar. Se o script gerador não existir, editar com `python-docx` (fazer backup antes).

---

## 1. Mapa rápido do projeto

| Caminho | O que contém |
|---|---|
| `routes/web.php` | Rotas de salas, pedidos, cozinha, estoque, relatório e dashboard |
| `routes/auth.php` | Rotas do Breeze (login, registro, reset, verificação) |
| `routes/channels.php` | Autorização do canal privado `sala.{roomId}` |
| `app/Http/Controllers/` | `RoomController`, `OrderController`, `KitchenController`, `CardapioController`, `ProfileController` |
| `app/Models/` | `Room`, `Order`, `OrderItem`, `Product`, `Category`, `BreadStock`, `User` |
| `app/Events/` | `OrderCreated`, `OrderUpdated`, `OrderDeleted`, `OrderStatusUpdated` (ShouldBroadcastNow) |
| `resources/views/layouts/` | `app` (sistema), `guest` (auth), `kitchen` (cozinha sem navbar, fundo escuro) |
| `resources/views/{rooms,orders,cozinha,cardapio,relatorios}/` | Telas do fluxo |
| `tests/Feature/` | Testes Pest (inclui `SegurancaTest.php`) |
| `tests/Pest.php` | Helpers `criarSalaComGarcom()` e `criarProduto()` |
| `database/migrations/` | Atenção: a tabela `rooms` é criada em `create_sessions_table.php` (nome enganoso) |

**Legado/fora de uso (não expandir):** `app/Http/Controllers/Admin/UserController.php`, `app/Http/Controllers/UserController.php` e `app/Http/Middleware/CheckIfIsAdmin.php` não estão roteados. Não remova sem autorização.

---

## 2. Ambiente e comandos

O PHP roda **dentro do Docker** (serviço `app`). O Node **não** está no container: `npm` roda na máquina host.

```bash
# Subir o ambiente
docker compose up -d

# Artisan / testes / composer (sempre no container)
docker compose exec -T app php artisan <comando>
docker compose exec -T app php artisan test
docker compose exec -T app php artisan view:clear
docker compose exec -T app composer audit

# Frontend (host)
npm run dev
npm run build

# Qualidade (somente nos arquivos tocados; o legado não é uniforme)
# Pint não aceita "app/..." — passe arquivos/diretórios reais:
docker compose exec -T app ./vendor/bin/pint --test app/Http/Controllers/OrderController.php tests/Feature/SegurancaTest.php
```

**Serviços:** app `http://localhost:8000` · phpMyAdmin `http://localhost:8080` · Reverb `8081` · MySQL `3300`.

**Testes:** `phpunit.xml` usa `sqlite :memory:`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `BROADCAST_CONNECTION=log`. **Nunca** aponte os testes para o MySQL de desenvolvimento nem use `migrate:fresh` no banco de dev.

---

## 3. Regras de ouro (não negociáveis)

1. **Analise antes de implementar**: rotas, controllers, models, views, testes e regras de autorização envolvidas.
2. **Preserve a arquitetura existente** e faça a **menor mudança** que resolve o problema. Não reescreva módulos sem necessidade.
3. **Não altere arquivos fora do escopo** sem justificativa.
4. **Não apague arquivos** nem sobrescreva alterações do usuário.
5. **Não desative mecanismos de segurança** para fazer testes passarem.
6. **Não exponha credenciais** (`.env` e afins). Não imprima segredos em logs/respostas.
7. **Não faça commit, push ou deploy sem autorização explícita** do usuário. Quando autorizado, siga o padrão da seção 8.
8. **Não instale/atualize dependências** sem justificar a necessidade e sem autorização quando não for essencial à tarefa.
9. **Não execute comandos destrutivos** (`rm -rf`, `git reset --hard`, `git clean -fd`, `DROP TABLE`, `migrate:fresh`) sem autorização.
10. **Não execute testes contra sistemas de terceiros** — apenas contra o ambiente local.
11. **Preserve arquivos pré-existentes estranhos**: `laravel` (SQLite vazio, versionado), `aberta,`, `[required,` (vazios, versionados) e `Niri/` (config pessoal do usuário). Não apague nem modifique sem autorização.

---

## 4. Fluxo de trabalho obrigatório

1. **Analisar** — entender o fluxo, os limites de confiança e o impacto.
2. **Planejar** — listar arquivos, riscos e a mudança mínima.
3. **Implementar** — incrementalmente, seguindo o estilo do código ao redor.
4. **Testar** — teste específico + suíte completa; verificar regressões.
5. **Revisar** — `git diff`, segurança (auth/IDOR/XSS/CSRF/SQLi) e escopo.
6. **Atualizar o Word** — se mudou comportamento, atualizar o documento de arquitetura.
7. **Relatar** — arquivos, o que mudou, como testar, riscos e o que não foi possível testar.
8. **Commitar** — somente com autorização, no padrão da seção 8.

---

## 5. Segurança (Security by Design)

### Limites de confiança do sistema
- **Visitante** (não logado) × **autenticado** × **participante da sala** × **garçom** × **cozinha** × **criador da sala**.
- Dados do frontend **nunca** são confiáveis: toda validação relevante é feita no backend.

### Invariantes de segurança que NÃO podem regredir
- Rotas de sala, pedido, cozinha, estoque e relatório ficam no grupo `Route::middleware('auth')`.
- `RoomController@show`: quem não é participante é redirecionado para a tela de senha; cozinha vai para a cozinha.
- `OrderController::garantirPedidoDaSala()`: todo pedido é validado contra a sala da URL (**anti-IDOR**, 404). Ao criar novas rotas de pedido, aplique a mesma checagem.
- `KitchenController@markReady`: confirma que o pedido pertence à sala antes de alterar.
- `RoomController@atualizarEstoque` e `relatorioVendas`: exigem `isGarcom`.
- `RoomController@encerrarForm/encerrar`: apenas `criado_por === Auth::id()`.
- `KitchenController@index`: exige `isParticipante`.
- `routes/channels.php`: o canal privado `sala.{roomId}` só autoriza participantes.
- `throttle:10,1` em criar sala e entrar em sala (força bruta de senha).
- `BreadStock::consumirSeDisponivel()`: baixa de pão **atômica** (`UPDATE ... WHERE quantidade >= N`) dentro de `DB::transaction`; nunca ler quantidade e decrementar em passos separados. `OrderController@update` usa `lockForUpdate` na linha do pedido.
- Senha de sala: mínimo **6** caracteres. `POST /register` tem `throttle:6,1`.
- Rota pública `/users` **não existe** (removida por vazar dados). Não recriar.
- CSRF: todo formulário usa `@csrf` (o partial `orders/_form.blade.php` já contém).
- Blade escapa saída com `{{ }}`. **Não use `{!! !!}` com dados de usuário.**
- SQL sempre parametrizado (query builder/Eloquent); **nunca** concatenar entrada em SQL.
- Senha da sala com `Hash::make` e campo `senha` oculto no model (`$hidden`).
- Nada de recurso externo (CDN, fonte, imagem de site) — projeto offline.

### Ao implementar, considere
Validação/sanitização de entrada; autorização em cada ação; XSS; CSRF; SQL Injection; **IDOR**; SSRF (se surgir HTTP externo); uploads (validar tipo/tamanho/armazenamento); condições de corrida; tratamento de erro sem vazar detalhes; proteção de credenciais e dados sensíveis.

### Pesquisa de vulnerabilidades
- Antes de mexer em dependências, rode `composer audit` e `npm audit`.
- Consulte **NVD**, **CVE.org**, **OSV.dev** e **GitHub Security Advisories** quando relevante.
- Investigue falhas de lógica e cadeias de vulnerabilidade sem CVE.
- **Diferencie** vulnerabilidade confirmada de hipótese. **Não invente descobertas.**

---

## 6. Riscos conhecidos e dívida técnica (estado em V0.7.2)

Estes itens **não** foram corrigidos. Se a tarefa tocar neles, **explique o risco antes** e só corrija com autorização:

- **RESOLVIDO (11/11/2026)** — `APP_ENV=production` e `APP_DEBUG=false` no `.env` (antes `local`/`true`). Não voltar a `APP_DEBUG=true` em ambiente alcançável pela rede. Para depurar localmente, use `APP_DEBUG=true` apenas temporariamente e nunca em produção.
- **RESOLVIDO (11/11/2026)** — Symfony atualizado para **7.4.23** (`symfony/mime` 7.4.22); `composer audit` sem advisories. Ao adicionar/atualizar dependências, rode `composer audit` de novo.
- **RESOLVIDO (11/11/2026)** — MySQL (`3300`) e phpMyAdmin (`8080`) agora escutam **somente em `127.0.0.1`**. nginx (`8000`) e Reverb (`8081`) continuam em `0.0.0.0` porque o tablet da cozinha precisa de LAN. Não reabra banco/admin em `0.0.0.0`.
- **RESOLVIDO (11/11/2026)** — Corrida no estoque de pão: `BreadStock::consumirSeDisponivel()` faz `UPDATE ... WHERE quantidade >= N` (atômico) dentro de `DB::transaction`, junto com a criação/edição do pedido; `lockForUpdate` protege a edição concorrente do mesmo pedido. Nunca volte a ler a quantidade e decrementar em passos separados.
- **RESOLVIDO (11/11/2026)** — Senha de sala agora exige **mínimo de 6 caracteres** (`RoomController@store` + `rooms/create.blade.php`). O `POST /register` ganhou `throttle:6,1`. O cadastro continua aberto (decisão pendente: exigir aprovação ou convite).
- **LOW** — Código morto: `Admin\UserController`, `UserController`, `CheckIfIsAdmin`, `StoreUserRequest::authorize()` retornando `true`.
- **LOW** — `composer.json` com `config.audit.block-insecure=false`.
- **INFORMATIONAL** — Estoque de pão é global, mas o ajuste é autorizado por sala (decisão de design, não falha).
- **INFORMATIONAL** — Arquivo `laravel` (SQLite) versionado na raiz; suja o repositório.

---

## 7. Testes e qualidade

- Framework: **Pest** (Feature e Unit). Banco: `sqlite :memory:`.
- Use os helpers de `tests/Pest.php` (`criarSalaComGarcom()`, `criarProduto()`).
- **Sempre** adicione teste de regressão para bug corrigido ou falha de segurança.
- Rode a suíte completa antes de concluir:
  `docker compose exec -T app php artisan test`
- Revise `git diff` e confirme que não há segredo, debug esquecido ou mudança fora do escopo.
- **Pint** está disponível, mas o código legado não é uniformemente formatado: formate apenas os arquivos tocados; não reformate o projeto inteiro.
- Não há PHPStan/Psalm configurado — registre essa limitação quando relevante.
- Se algum teste não puder ser executado, **diga explicitamente** o motivo.

---

## 8. Convenções do projeto

- **Idioma:** UI e comunicação em PT-BR.
- **Commits (somente com autorização):** `TC V<major>.<minor>.<patch> <descrição>` — ex.: `TC V0.7.2 segurança: ...`.
- **UX:** campos numéricos **não** vêm preenchidos com `0`; usar placeholder (ex.: `Ex: 120` no pão, `0` no pedido).
- **Layouts:** `x-app-layout` (sistema), `x-guest-layout` (auth), `x-kitchen-layout` (cozinha sem navbar, fundo escuro).
- **Responsivo:** funciona bem em celular e PC; use classes responsivas do Tailwind.
- **Offline:** nenhum recurso externo.
- **Word:** após mudança de comportamento, atualizar `/home/kazuma/Downloads/arquitetura-cardapio.docx` (seções 0, 10, 12 e 14 costumam ser afetadas).
- **README** pode estar desatualizado; a fonte de verdade é o código + Word. Não reescreva o README sem necessidade.

---

## 9. Armadilhas conhecidas (leia antes de mexer)

- **Tailwind:** `package.json` declara `^4.3.3`, mas o `node_modules`/build atual é **v3.4.19** e `resources/css/app.css` usa diretivas v3 (`@tailwind base/components/utilities`) + `tailwind.config.js`. **Não rode `npm install`/`npm update` nem migre para v4 sem autorização** — pode quebrar todo o CSS.
- **Tabela `rooms`:** criada dentro de `create_sessions_table.php` (nome enganoso). Não presuma uma migration `create_rooms_table`.
- **dompdf 3.x:** `stream()` retorna `Illuminate\Http\Response` (não `StreamedResponse`). O relatório usa headers anti-cache (`no-store`/`no-cache`) + cache-busting.
- **Alpine.js:** `@click.outside` deve ficar no **wrapper** (botão + pop-up), não só no botão.
- **Breeze:** o menu superior assume usuário logado; qualquer view pública com `x-app-layout` precisa de guard `auth()->check()`.
- **Nginx cacheia o IP do upstream:** ao recriar o container `app` (`docker compose up -d`, rebuild, etc.), o nginx continua apontando para o IP antigo e o site retorna **502**. Sempre rode `docker compose restart nginx` depois de recriar o `app`.
- **Imagem do container:** o `Dockerfile` usa `php:8.4-fpm`; a imagem atual roda PHP 8.4.26. Ao trocar de máquina ou após muito tempo, rode `docker compose build --no-cache app reverb` para não voltar a uma base defasada.
- **`orders.criado_por`** é `NOT NULL`; rotas que criam pedido dependem de usuário autenticado.
- **Arquivo `laravel`:** SQLite vazio na raiz, versionado. Os testes usam `:memory:`; não o use como banco de dev.
- **Arquivos `aberta,` e `[required,`:** vazios e versionados; não apague sem autorização.
- **`Niri/`:** configuração pessoal do usuário; não é parte do sistema.

---

## 10. Eficiência de contexto (economia de tokens sem perder qualidade)

- **Não leia** `vendor/`, `node_modules/`, `storage/framework/views/`, `public/build/` nem arquivos gerados.
- Use `rg`/`grep` para localizar e leia apenas os trechos necessários.
- Não repita análises já feitas; resuma tarefas longas.
- Para exploração ampla, use subagentes (quando disponíveis) em vez de despejar muitos arquivos no contexto.
- Escolha o modelo adequado à complexidade da tarefa; **não sacrifique qualidade** para economizar tokens.
- **RTK** (Rust Token Killer) está configurado globalmente no Claude Code (`~/.claude/RTK.md`): use normalmente; o hook otimiza a saída. **Headroom**: se estiver configurado no ambiente, siga as instruções dele; não há configuração documentada neste projeto.

---

## 11. Como responder

1. Comece com um **plano curto** (o que vai fazer e por quê).
2. Explique decisões técnicas importantes em **linguagem simples** (o usuário está aprendendo).
3. Ao final, entregue um **relatório** com:
   - arquivos modificados;
   - o que foi implementado;
   - proteções de segurança adicionadas/revisadas;
   - testes executados (e o que não foi possível testar);
   - riscos residuais;
   - próximos passos.
4. Nunca afirme que o projeto está "totalmente seguro" só porque os testes passaram.

---

## 12. Checklist antes de finalizar

- [ ] Entendi o fluxo e os limites de confiança envolvidos.
- [ ] A mudança é mínima e está dentro do escopo.
- [ ] Adicionei/ajustei testes para o comportamento novo.
- [ ] Rodei a suíte completa e não há regressão.
- [ ] Revisei o `git diff` (sem segredos, sem debug esquecido).
- [ ] Revisei segurança: auth, IDOR, XSS, CSRF, SQLi, exposição de dados.
- [ ] Atualizei o Word (se mudou comportamento).
- [ ] Não fiz commit/push/deploy sem autorização.
- [ ] Informei riscos e limitações.
