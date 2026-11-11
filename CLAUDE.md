# CLAUDE.md — Notas específicas do Claude Code

@AGENTS.md

> As instruções principais deste repositório estão em **`AGENTS.md`**, importado acima.
> Este arquivo contém **apenas** o que é específico do Claude Code. Em caso de dúvida,
> `AGENTS.md` é a fonte de verdade — não duplique regras aqui.

---

## 1. Fluxo recomendado no Claude Code

1. **Explore antes de editar**: use `Glob`/`Grep`/`Read` para localizar o código relevante.
   Não leia `vendor/`, `node_modules/`, `storage/framework/views/` nem `public/build/`.
2. **Planeje em Plan Mode** para tarefas com mais de um arquivo ou com impacto de segurança:
   apresente o plano, aguarde aprovação e só então edite.
3. **Use a todo list** (`TodoWrite`) para tarefas com 3+ passos; mantenha um item em
   andamento por vez.
4. **Delegue exploração ampla** ao subagente `Explore` (ou `Task`) para economizar contexto,
   mas **revise** o resultado antes de confiar nele.
5. **Edite incrementalmente** com `Edit`/`Write`; prefira `Edit` para mudanças pontuais.
6. **Verifique** com testes, `git diff` e revisão de segurança antes de concluir.

## 2. Permissões e comandos

- Comandos PHP **sempre** via `docker compose exec -T app ...` (o PHP não roda no host).
  Exemplo: `docker compose exec -T app php artisan test`.
- `npm` roda no **host** (Node não está no container).
- Use o **prefixo `Bash(...)`** apenas quando necessário; não peça permissões amplas.
- **Não** execute `git commit`, `git push`, `git reset --hard`, `git clean -fd`, `rm -rf`,
  `migrate:fresh` ou `composer update` sem autorização explícita (ver `AGENTS.md` §3).

## 3. RTK (Rust Token Killer)

- O RTK está configurado **globalmente** em `~/.claude/RTK.md` (importado pelo `CLAUDE.md`
  global). O hook reescreve comandos de shell automaticamente para economizar tokens —
  **não** é preciso chamar `rtk` manualmente.
- Comandos de meta-análise (usar direto): `rtk gain`, `rtk gain --history`,
  `rtk discover`, `rtk proxy <cmd>`.
- Se `rtk gain` falhar, pode haver colisão de nome (Rust Type Kit) — ver `~/.claude/RTK.md`.
- **Headroom**: se estiver ativo no ambiente, siga as instruções dele; não há configuração
  documentada neste projeto.

## 4. Memória e imports

- `CLAUDE.md` importa `AGENTS.md` com a sintaxe `@AGENTS.md` (linha acima).
- Mantenha **este** arquivo curto: regras gerais vão em `AGENTS.md`; aqui só o que for
  exclusivo do Claude Code (Plan Mode, todo list, subagentes, permissões, RTK).
- Ao mudar uma regra que vale para todos os agentes, edite `AGENTS.md` — não este arquivo.

## 5. Respostas

- Idioma: **PT-BR**. O usuário está aprendendo programação — explique decisões importantes
  de forma simples, sem transformar toda resposta em um texto longo.
- Finalize com um **relatório curto**: arquivos alterados, o que mudou, testes executados,
  riscos residuais e o que **não** foi possível testar.
- **Nunca** afirme que o projeto está "totalmente seguro" só porque os testes passaram.
