#!/usr/bin/env bash
set -euo pipefail

# ── Args (injected as env vars by the runner) ────────────────────────────────
MC_VERSION="${MC_VERSION:-LATEST}"
ONLINE_MODE="${ONLINE_MODE:-true}"
SERVER_TYPE="${SERVER_TYPE:-PAPER}"
MEMORY="${MEMORY:-2G}"
INSTALL_DIR="/opt/minecraft"

# ── Helpers ──────────────────────────────────────────────────────────────────
ok()   { echo "[ok]   $*"; }
info() { echo "[info] $*"; }
err()  { echo "[error] $*" >&2; exit 1; }
hr()   { echo "────────────────────────────────────────"; }

# ── Find a free port starting from 25565 ─────────────────────────────────────
find_free_port() {
    local port=25565
    while ss -tuln 2>/dev/null | grep -q ":$port " || \
          netstat -tuln 2>/dev/null | grep -q ":$port "; do
        port=$((port + 1))
    done
    echo "$port"
}

SERVER_PORT=$(find_free_port)

hr
info "Minecraft Server Installer"
info "Version   : $MC_VERSION"
info "Auth Mode : $([ "$ONLINE_MODE" = "true" ] && echo 'Mojang Auth ON (premium accounts only)' || echo 'Mojang Auth OFF (any account allowed)')"
info "Type      : $SERVER_TYPE"
info "Memory    : $MEMORY"
info "Port      : $SERVER_PORT"
hr

# ── OS check ─────────────────────────────────────────────────────────────────
[[ ! -f /etc/os-release ]] && err "Cannot detect OS"
. /etc/os-release
OS_ID="${ID,,}"
case "$OS_ID" in
  ubuntu|debian|raspbian) ;;
  *) err "Unsupported OS: $OS_ID (Debian/Ubuntu only)" ;;
esac

export DEBIAN_FRONTEND=noninteractive

# ── Docker ───────────────────────────────────────────────────────────────────
if ! command -v docker &>/dev/null; then
  info "Installing Docker..."
  apt-get -qq update
  apt-get -qq install -y ca-certificates curl
  install -m 0755 -d /etc/apt/keyrings
  case "$OS_ID" in
    ubuntu)
      curl -fsSL "https://download.docker.com/linux/ubuntu/gpg" -o /etc/apt/keyrings/docker.asc
      echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/ubuntu $VERSION_CODENAME stable" \
        > /etc/apt/sources.list.d/docker.list
      ;;
    debian|raspbian)
      curl -fsSL "https://download.docker.com/linux/debian/gpg" -o /etc/apt/keyrings/docker.asc
      echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/debian $VERSION_CODENAME stable" \
        > /etc/apt/sources.list.d/docker.list
      ;;
  esac
  chmod a+r /etc/apt/keyrings/docker.asc
  apt-get -qq update
  apt-get -qq install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
  systemctl enable --now docker
  ok "Docker installed"
else
  DOCKER_VER=$(docker version --format "{{.Server.Version}}" 2>/dev/null || echo "unknown")
  ok "Docker already installed — $DOCKER_VER"
fi

# ── Verify docker compose plugin ─────────────────────────────────────────────
if ! docker compose version &>/dev/null; then
  info "Installing docker-compose-plugin..."
  apt-get -qq install -y docker-compose-plugin
fi
ok "Docker Compose ready"

# ── Prepare directories ───────────────────────────────────────────────────────
info "Creating server directory at $INSTALL_DIR ..."
mkdir -p "$INSTALL_DIR"/{data,plugins,mods,config}

# ── Vanilla: pre-download jar via curl to bypass Java/Netty SSL issues ────────
COMPOSE_TYPE="$SERVER_TYPE"
COMPOSE_EXTRA_ENV=""

if [[ "$SERVER_TYPE" = "VANILLA" ]]; then
    info "Vanilla: resolving version via curl (bypasses Java SSL handshake timeout)..."

    MANIFEST=$(curl -fsSL "https://launchermeta.mojang.com/mc/game/version_manifest_v2.json") \
        || err "Failed to fetch Mojang version manifest"

    if [[ "$MC_VERSION" = "LATEST" ]]; then
        MC_VERSION=$(python3 -c "import sys,json; d=json.loads('''$MANIFEST'''); print(d['latest']['release'])" 2>/dev/null) \
            || MC_VERSION=$(echo "$MANIFEST" | grep -o '"release": *"[^"]*"' | head -1 | grep -o '"[^"]*"$' | tr -d '"')
    fi
    info "Resolved version: $MC_VERSION"

    VERSION_URL=$(python3 -c "
import sys, json
data = json.loads(open('/dev/stdin').read())
target = '$MC_VERSION'
for v in data['versions']:
    if v['id'] == target:
        print(v['url']); break
" <<< "$MANIFEST") || err "Version $MC_VERSION not found in manifest"

    SERVER_URL=$(curl -fsSL "$VERSION_URL" | python3 -c "
import sys, json
d = json.load(sys.stdin)
print(d['downloads']['server']['url'])
") || err "Failed to get server download URL"

    info "Downloading $MC_VERSION server jar..."
    curl -fL --progress-bar -o "$INSTALL_DIR/data/minecraft_server.jar" "$SERVER_URL" \
        || err "Failed to download server jar"
    ok "Server jar downloaded — $(du -sh "$INSTALL_DIR/data/minecraft_server.jar" | cut -f1)"

    # Tell itzg to use the pre-downloaded jar instead of fetching it
    COMPOSE_TYPE="CUSTOM"
    COMPOSE_EXTRA_ENV='      CUSTOM_SERVER: "/data/minecraft_server.jar"'
fi

# ── docker-compose.yml ───────────────────────────────────────────────────────
info "Writing docker-compose.yml ..."
cat > "$INSTALL_DIR/docker-compose.yml" <<EOF
services:
  minecraft:
    image: itzg/minecraft-server:latest
    container_name: minecraft
    network_mode: host
    environment:
      EULA: "TRUE"
      VERSION: "$MC_VERSION"
      TYPE: "$COMPOSE_TYPE"
      ONLINE_MODE: "$ONLINE_MODE"
      MEMORY: "$MEMORY"
      SERVER_PORT: "$SERVER_PORT"
      USE_AIKAR_FLAGS: "true"
      ENABLE_RCON: "true"
      RCON_PASSWORD: "minecraft_rcon"
      ALLOW_NETHER: "true"
      SPAWN_ANIMALS: "true"
      SPAWN_MONSTERS: "true"
      MAX_PLAYERS: "20"
      VIEW_DISTANCE: "10"
      MOTD: "Minecraft Server — rawhost.net"
$COMPOSE_EXTRA_ENV
    volumes:
      - ${INSTALL_DIR}/data:/data
      - ${INSTALL_DIR}/plugins:/data/plugins
      - ${INSTALL_DIR}/mods:/data/mods
      - ${INSTALL_DIR}/config:/data/config
    restart: unless-stopped
    stdin_open: true
    tty: true
EOF

ok "docker-compose.yml written"

# ── Pull image ────────────────────────────────────────────────────────────────
info "Pulling itzg/minecraft-server image (this may take a few minutes)..."
docker pull itzg/minecraft-server:latest
ok "Image pulled"

# ── Start ─────────────────────────────────────────────────────────────────────
info "Starting Minecraft server..."
cd "$INSTALL_DIR"

# Stop existing instance if any
docker compose down --remove-orphans 2>/dev/null || true

# Detect server type mismatch — world data from a different type (e.g. Forge) will
# crash Vanilla/Paper on startup with "Missing data pack" errors. Wipe world only.
if [[ -f "$INSTALL_DIR/data/.server_type" ]]; then
    PREV_TYPE=$(cat "$INSTALL_DIR/data/.server_type")
    if [[ "$PREV_TYPE" != "$SERVER_TYPE" ]]; then
        info "Server type changed ($PREV_TYPE → $SERVER_TYPE). Removing old world data..."
        rm -rf "$INSTALL_DIR/data/world" \
               "$INSTALL_DIR/data/world_nether" \
               "$INSTALL_DIR/data/world_the_end" \
               "$INSTALL_DIR/data/level.dat" \
               "$INSTALL_DIR/data/level.dat_old" \
               "$INSTALL_DIR/data/datapacks"
        ok "Old world removed"
    fi
fi
echo "$SERVER_TYPE" > "$INSTALL_DIR/data/.server_type"

docker compose up -d
ok "Container started"

# ── Wait for server to initialize ─────────────────────────────────────────────
info "Waiting for server to initialize (up to 8 min)..."
WAITED=0
until docker compose logs minecraft 2>/dev/null | grep -q "Done\|DONE\|For help, type"; do
  sleep 10
  WAITED=$((WAITED + 10))
  if [[ $WAITED -ge 480 ]]; then
    info "Server is still starting in background (large download for first run is normal)"
    break
  fi
  echo "  ... ${WAITED}s"
done

hr
ok "Minecraft server is running"
echo ""
echo "  Port    : $SERVER_PORT"
echo "  Version : $MC_VERSION"
echo "  Type    : $SERVER_TYPE"
echo "  Auth    : $([ "$ONLINE_MODE" = "true" ] && echo 'Mojang ON (premium only)' || echo 'Mojang OFF (any account)')"
echo "  Data    : $INSTALL_DIR/data"
echo "  Plugins : $INSTALL_DIR/plugins"
echo "  Mods    : $INSTALL_DIR/mods"
echo ""
echo "  Useful commands:"
echo "    cd $INSTALL_DIR && docker compose logs -f    # follow logs"
echo "    cd $INSTALL_DIR && docker compose down        # stop server"
echo "    cd $INSTALL_DIR && docker compose restart     # restart server"
hr
