#!/usr/bin/env bash
set -e

result_ok()    { echo "{\"status\":\"$1\",\"msg\":\"$2\"}"; exit 0; }
result_error() { echo "{\"status\":\"error\",\"error\":\"$1\"}"; exit "${2:-1}"; }

[[ ! -f /etc/os-release ]] && result_error "cannot detect OS"

. /etc/os-release
OS_ID="${ID,,}"

echo "[info] OS: $PRETTY_NAME"

case "$OS_ID" in
  ubuntu|debian|raspbian) ;;
  *) result_error "Unsupported OS: $OS_ID (Debian/Ubuntu only)" ;;
esac

if ! command -v docker &>/dev/null; then
  echo "[ok] Docker is not installed — nothing to do"
  result_ok "not_installed" "Docker was not present"
fi

echo "[info] Stopping running containers..."
docker ps -q | xargs -r docker stop 2>/dev/null || true

echo "[info] Removing Docker packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get -qq remove -y \
  docker-ce docker-ce-cli containerd.io docker-compose-plugin \
  docker-ce-rootless-extras docker-buildx-plugin 2>/dev/null || true
apt-get -qq autoremove -y 2>/dev/null || true

echo "[info] Removing Docker data and config..."
rm -rf /var/lib/docker /var/lib/containerd
rm -f /etc/apt/sources.list.d/docker.list /etc/apt/keyrings/docker.asc

echo "[ok] Docker removed"
result_ok "removed" "Docker and all its data have been removed"
