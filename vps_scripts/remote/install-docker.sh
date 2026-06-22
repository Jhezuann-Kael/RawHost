#!/usr/bin/env bash
set -e

result_ok()    { echo "{\"status\":\"$1\",\"version\":\"$2\"}"; exit 0; }
result_error() { echo "{\"status\":\"error\",\"error\":\"$1\"}"; exit "${2:-1}"; }

[[ ! -f /etc/os-release ]] && result_error "no se puede detectar el OS"

. /etc/os-release
OS_ID="${ID,,}"

echo "[info] OS: $PRETTY_NAME"

case "$OS_ID" in
  ubuntu|debian|raspbian) ;;
  *) result_error "OS no soportado: $OS_ID (solo Debian/Ubuntu)" ;;
esac

if command -v docker &>/dev/null; then
  VERSION=$(docker version --format "{{.Server.Version}}" 2>/dev/null || echo "unknown")
  echo "[ok] Docker ya instalado — $VERSION"
  result_ok "already_installed" "$VERSION"
fi

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

echo "[info] Verificando..."
if ! docker run --rm hello-world &>/dev/null; then
  result_error "Docker instalado pero fallo al correr contenedor de prueba" 2
fi

VERSION=$(docker version --format "{{.Server.Version}}")
echo "[ok] Docker $VERSION listo"
result_ok "ok" "$VERSION"
