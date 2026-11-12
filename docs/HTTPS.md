# HTTPS, TLS e acesso multidispositivo

> Guia da infraestrutura de HTTPS/WSS do Tela_d_Cardapio.
> Última atualização: 11/11/2026 (V0.8.0).

## 1. Como funciona

```
celular / tablet / PC
        │  HTTPS 443  e  WSS 443
        ▼
   ┌──────────┐
   │  Caddy   │  única porta pública (80 redireciona para 443)
   └────┬─────┘
        │ rede interna do Docker (172.19.0.0/16)
        ├── /        → nginx:80 → php-fpm (Laravel)
        └── /app/*   → reverb:8081 (WebSocket)
```

- O **Caddy** termina o TLS com uma **CA local** (`tls internal`).
- `nginx`, `reverb`, `app`, `db` e `redis` **não são publicados** no host.
- MySQL (`3300`) e phpMyAdmin (`8080`) continuam **somente em `127.0.0.1`**.
- CORS não é usado: frontend e backend são a mesma origem.
- CSRF continua ativo em todos os formulários.
- Cookies de sessão usam `Secure`, `HttpOnly` e `SameSite=Lax`; a sessão é criptografada (`SESSION_ENCRYPT=true`).

## 2. Endereços de acesso

| Endereço | Quando usar |
|---|---|
| `https://cardapio.local` | Endereço amigável via mDNS (alias publicado pelo Avahi) |
| `https://kazuarch.local` | Nome mDNS da máquina (já publicado pelo Avahi/systemd-resolved) |
| `https://192.168.100.114` | IP atual (muda quando o servidor troca de rede) |
| `https://localhost` | Somente na própria máquina |

O certificado cobre `cardapio.local`, `kazuarch.local`, `localhost`, `127.0.0.1` e o IP atual.

> **Servidor móvel:** o nome mDNS é o endereço recomendado. O IP no certificado é só uma conveniência; se o IP mudar, use o nome ou atualize `CARDAPIO_IP` no `.env` e recrie o Caddy:
> ```bash
> docker compose up -d --force-recreate caddy
> ```

### Alias `cardapio.local` (mDNS)

O Avahi já publica o hostname da máquina. O alias amigável é publicado por um serviço de usuário:

```bash
# já instalado; para conferir:
systemctl --user status cardapio-mdns.service
avahi-resolve -n cardapio.local
```

O script `scripts/mdns-cardapio.sh` detecta o IP da LAN a cada 15 s e republica o alias quando o IP muda. Se o serviço não estiver ativo:

```bash
systemctl --user enable --now cardapio-mdns.service
```

Em dispositivos sem mDNS, use `kazuarch.local` (se o sistema tiver Bonjour/mDNS) ou o IP atual (aceitando o aviso de certificado, pois o IP pode não estar no certificado).

## 3. Subir, reiniciar e derrubar

```bash
docker compose up -d
docker compose ps
docker compose logs -f caddy
```

Depois de recriar o container `app`, reinicie o nginx (ele guarda o IP antigo do upstream):

```bash
docker compose restart nginx
```

## 4. Certificado da CA local

A CA é criada e guardada no volume Docker `tela_d_cardapio_caddy_data`.

- **Chave privada:** `/data/caddy/pki/authorities/local/root.key` — **nunca** sai do volume e **nunca** deve ser copiada para dispositivos ou para o repositório.
- **Certificado público:** `root.crt` — é o único arquivo que vai para os dispositivos.

Exportar o certificado público para a pasta Downloads:

```bash
cd /home/kazuma/Projetos/Tela_d_Cardapio
docker compose cp caddy:/data/caddy/pki/authorities/local/root.crt /home/kazuma/Downloads/cardapio-ca-raiz.crt
```

> Se o volume `caddy_data` for apagado, a CA muda. Todos os dispositivos precisam instalar o novo certificado.

## 5. Instalação da CA nos dispositivos

Sem instalar a CA, o navegador mostra aviso de certificado não confiável (o HTTPS funciona, mas com alerta).

### Android

1. Copie `cardapio-ca-raiz.crt` para o celular (cabo, e-mail, Google Drive etc.).
2. Abra **Configurações → Segurança → Criptografia e credenciais → Instalar certificado → Certificado de CA**.
3. Confirme o aviso e escolha o arquivo `cardapio-ca-raiz.crt`.
4. O Chrome e a maioria dos navegadores passam a confiar.
5. **Limitação:** a partir do Android 7, aplicativos que usam a CA do sistema **não** confiam automaticamente em CA instalada pelo usuário. O navegador confia; um app próprio precisaria de configuração de *network security config*.

### iOS / iPadOS

1. Envie o arquivo para o dispositivo (AirDrop, Arquivos, e-mail).
2. Toque no arquivo e instale o **perfil** em **Ajustes → Geral → VPN e Gerenciamento de Dispositivos**.
3. Depois de instalar, ative a confiança total em **Ajustes → Geral → Sobre → Confiança de Certificados** e ligue a chave para a CA.
4. Sem o passo 3 o Safari continua avisando.

### Windows

1. Copie `cardapio-ca-raiz.crt` para o PC.
2. Abra `certlm.msc` (Certificados do Computador Local).
3. Vá em **Autoridades de Certificação Raiz Confiáveis → Certificados**.
4. Botão direito → **Todas as Tarefas → Importar** → escolha o arquivo → conclua.
5. Alternativa por linha de comando (PowerShell como administrador):
   ```powershell
   certutil -addstore -f "Root" .\cardapio-ca-raiz.crt
   ```
6. Chrome e Edge usam a loja do Windows. O Firefox pode ter loja própria: **Configurações → Privacidade e Segurança → Certificados → Ver Certificados → Autoridades → Importar**.

### Linux

Debian/Ubuntu e derivados:

```bash
sudo cp cardapio-ca-raiz.crt /usr/local/share/ca-certificates/cardapio-ca.crt
sudo update-ca-certificates
```

Arch Linux:

```bash
sudo trust anchor --store cardapio-ca-raiz.crt
sudo trust extract-compat
```

Firefox (qualquer distro): **Configurações → Privacidade e Segurança → Certificados → Ver Certificados → Autoridades → Importar**.

### Conferir se a CA foi aceita

```bash
curl --cacert cardapio-ca-raiz.crt https://cardapio.local/ -o /dev/null -w "%{http_code}\n"
```

Se retornar `200` sem `-k`, o certificado está confiável.

## 6. Firewall (LAN apenas)

O servidor é **móvel**, então o firewall deve liberar as faixas privadas (RFC 1918), não uma rede fixa. **Não** faça redirecionamento de portas no roteador.

Comandos sugeridos (revise antes de executar; exigem `sudo`):

```bash
sudo ufw status numbered

# Porta 80 (só redireciona para 443)
sudo ufw allow from 10.0.0.0/8     to any port 80 proto tcp
sudo ufw allow from 172.16.0.0/12  to any port 80 proto tcp
sudo ufw allow from 192.168.0.0/16 to any port 80 proto tcp

# Porta 443 (HTTPS/WSS)
sudo ufw allow from 10.0.0.0/8     to any port 443 proto tcp
sudo ufw allow from 172.16.0.0/12  to any port 443 proto tcp
sudo ufw allow from 192.168.0.0/16 to any port 443 proto tcp

sudo ufw status numbered
```

Observações importantes:

- **Docker publica portas via iptables e pode ignorar regras do ufw.** Se quiser garantia, teste de outro dispositivo e, se necessário, use a cadeia `DOCKER-USER` (mais avançado).
- As portas `3300` (MySQL), `8080` (phpMyAdmin), `8000` e `8081` **não** estão mais publicadas na LAN. Não as reabra.
- Sem port-forward no roteador, a aplicação não é alcançável pela internet.
- Para acesso externo autorizado, prefira **VPN** (WireGuard/Tailscale) em vez de expor a porta 443.

## 7. Verificação rápida

```bash
# 1. HTTP redireciona para HTTPS (308)
curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" http://localhost/

# 2. HTTPS válido com a CA local
curl --cacert /home/kazuma/Downloads/cardapio-ca-raiz.crt https://localhost/ -o /dev/null -w "%{http_code}\n"

# 3. TLS 1.2 e 1.3
openssl s_client -connect localhost:443 -tls1_2 </dev/null 2>/dev/null | grep Protocol
openssl s_client -connect localhost:443 -tls1_3 </dev/null 2>/dev/null | grep Protocol

# 4. Cookie de sessão com Secure/HttpOnly/SameSite
curl -sI https://localhost/login | grep -i set-cookie
```

Teste de WSS (handshake WebSocket pelo Caddy):

```bash
KEY=$(head -c 16 /dev/urandom | base64)
curl -si --http1.1 -H "Connection: Upgrade" -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" -H "Sec-WebSocket-Key: $KEY" \
  -H "Origin: https://localhost" \
  "https://localhost/app/local-app-key?protocol=7&client=js&version=8.4.0&flash=false" | head -1
```

O esperado é `HTTP/1.1 101 Switching Protocols`.

## 8. Auditoria e sessões

- A tabela `audit_logs` registra login, falha de login, bloqueio por tentativas, 403, criação/encerramento de sala, entrada em sala (e senha errada), ajuste de estoque, criação/edição/exclusão de pedido e mudança de status.
- Senhas, tokens, cookies e segredos são removidos dos metadados.
- Retenção: **90 dias** (`AUDIT_RETENTION_DAYS`). A limpeza roda diariamente pelo container `scheduler` (`php artisan model:prune`).
- O menu do usuário tem **“Sair de todos os dispositivos”**: apaga as sessões da tabela `sessions` e invalida cookies de “lembrar-me”.
- O middleware `AuthenticateSession` derruba as outras sessões quando a senha muda.

## 9. Rollback

1. Restaurar `docker-compose.yml`, `docker/nginx/laravel.conf`, `bootstrap/app.php`, `config/reverb.php`, `resources/js/echo.js` e `.env` do backup.
2. `docker compose up -d`
3. `docker compose restart nginx`
4. `npm run build`
5. Remover a CA dos dispositivos, se desejar.

Os volumes, o banco e os dados **não** são alterados pelo rollback.

## 10. Riscos residuais

- CA instalada em dispositivo comprometido pode ser usada para interceptar tráfego daquele dispositivo (limitação inerente a CA local).
- Wi-Fi compartilhado: qualquer pessoa na mesma rede pode tentar login; a autorização e o rate limit continuam valendo.
- Cadastro aberto: qualquer pessoa na LAN pode criar conta. Mantido com `throttle:6,1`; avaliar convite/aprovação no futuro.
- Apps Android 7+ não confiam automaticamente em CA de usuário.
- Se o IP mudar e o nome mDNS não funcionar no dispositivo, o certificado do IP antigo não vale mais.
- HSTS é ignorado por navegadores quando o endereço é um IP; para HSTS valer, use o nome (`cardapio.local`/`kazuarch.local`).
