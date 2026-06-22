#!/usr/bin/env bash
# Usage:
#   ./install-docker.sh user@host
#   ./install-docker.sh user@host -i ~/.ssh/id_rsa
#
# Exit codes:
#   0  — Docker operativo (instalado ahora o ya existía)
#   1  — OS no soportado o error de instalación
#   2  — Docker instalado pero no funciona (verify falló)
#
# Última línea de stdout siempre es JSON:
#   {"status":"ok","version":"27.3.1","installed":true}
#   {"status":"already_installed","version":"27.3.1","installed":false}
#   {"status":"error","error":"mensaje"}

set -e

TARGET="$1"
shift
SSH_OPTS=("$@")

if [[ -z "$TARGET" ]]; then
  echo "Usage: $0 user@host [-i /path/to/key] [ssh options]"
  exit 1
fi

REMOTE='
set -e

result_ok()    { echo "{\"status\":\"$1\",\"version\":\"$2\"}"; exit 0; }
result_error() { echo "{\"status\":\"error\",\"error\":\"$1\"}"; exit "${2:-1}"; }

# ── Detect OS ──────────────────────────────────────────────────────────────────
[[ ! -f /etc/os-release ]] && result_error "no se puede detectar el OS"

. /etc/os-release
OS_ID="${ID,,}"

echo "[info] OS: $PRETTY_NAME"

case "$OS_ID" in
  ubuntu|debian|raspbian) ;;
  *) result_error "OS no soportado: $OS_ID (solo Debian/Ubuntu)" ;;
esac

# ── Verificar instalación existente ───────────────────────────────────────────
if command -v docker &>/dev/null; then
  VERSION=$(docker version --format "{{.Server.Version}}" 2>/dev/null || echo "unknown")
  echo "[ok] Docker ya instalado — $VERSION"
  result_ok "already_installed" "$VERSION"
fi

# ── Instalar Docker ───────────────────────────────────────────────────────────
echo "[info] Instalando Docker..."

export DEBIAN_FRONTEND=noninteractive
apt-get -qq update
apt-get -qq install -y ca-certificates curl

install -m 0755 -d /etc/apt/keyrings

case "$OS_ID" in
  ubuntu)
    curl -fsSL "https://download.docker.com/linux/ubuntu/gpg" -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/ubuntu $VERSION_CODENAME stable" \
      > /etc/apt/sources.list.d/docker.list
    ;;
  debian|raspbian)
    curl -fsSL "https://download.docker.com/linux/debian/gpg" -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/debian $VERSION_CODENAME stable" \
      > /etc/apt/sources.list.d/docker.list
    ;;
esac

apt-get -qq update
apt-get -qq install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
systemctl enable --now docker

# ── Verificar que Docker funciona de verdad ───────────────────────────────────
echo "[info] Verificando..."
if ! docker run --rm hello-world &>/dev/null; then
  result_error "Docker instalado pero fallo al correr contenedor de prueba" 2
fi

VERSION=$(docker version --format "{{.Server.Version}}")
echo "[ok] Docker $VERSION listo"
result_ok "ok" "$VERSION"
'

ssh "${SSH_OPTS[@]}" "$TARGET" "bash -s" <<< "$REMOTE"
