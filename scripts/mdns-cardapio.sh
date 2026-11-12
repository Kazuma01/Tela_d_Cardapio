#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Publica o alias mDNS "cardapio.local" apontando para o IP atual da LAN.
#
# Por que existe: o servidor é móvel e o IP muda quando ele troca de rede.
# O nome mDNS continua o mesmo, então o certificado HTTPS e os favoritos dos
# dispositivos não quebram. O Avahi já publica o hostname da máquina
# (KazuArch.local); este script adiciona o apelido cardapio.local.
#
# Uso manual:  ./scripts/mdns-cardapio.sh
# Como serviço: scripts/systemd/cardapio-mdns.service (instruções no AGENTS.md)
# ---------------------------------------------------------------------------
set -u

NOME="${CARDAPIO_MDNS_NOME:-cardapio.local}"
INTERVALO="${CARDAPIO_MDNS_INTERVALO:-15}"

ip_atual() {
    # Prefere a interface da rota padrão (funciona mesmo sem internet).
    local ip
    ip="$(ip -4 route get 1.1.1.1 2>/dev/null | awk '{print $7; exit}')"

    # Sem rota padrão (LAN isolada): pega a primeira interface global que não
    # seja de Docker/VPN/loopback.
    if [ -z "$ip" ]; then
        ip="$(ip -4 -o addr show scope global 2>/dev/null \
            | awk '$2 !~ /^(docker|br-|veth|virbr|tun|tap|lo)/ {split($4, a, "/"); print a[1]; exit}')"
    fi

    printf '%s' "$ip"
}

parar() {
    if [ -n "${pid:-}" ] && kill -0 "$pid" 2>/dev/null; then
        kill "$pid" 2>/dev/null
        wait "$pid" 2>/dev/null
    fi
}

trap 'parar; exit 0' INT TERM

while true; do
    ip="$(ip_atual)"

    if [ -z "$ip" ]; then
        sleep "$INTERVALO"
        continue
    fi

    avahi-publish -a -R "$NOME" "$ip" &
    pid=$!

    # Mantém a publicação enquanto o IP não mudar e o processo estiver vivo.
    while kill -0 "$pid" 2>/dev/null; do
        sleep "$INTERVALO"
        novo="$(ip_atual)"
        [ "$novo" = "$ip" ] || break
    done

    parar
    sleep 2
done
