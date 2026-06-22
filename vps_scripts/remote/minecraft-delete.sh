#!/usr/bin/env bash
set -euo pipefail

# ── Config ───────────────────────────────────────────────────────────────────
INSTALL_DIR="/opt/minecraft"

# ── Helpers ──────────────────────────────────────────────────────────────────
ok()   { echo "[ok]   $*"; }
info() { echo "[info] $*"; }
err()  { echo "[error] $*" >&2; exit 1; }
hr()   { echo "────────────────────────────────────────"; }

hr
info "Minecraft Server Uninstaller"
info "Path : $INSTALL_DIR"
hr

# ── Check if Docker is installed ─────────────────────────────────────────────
if ! command -v docker &>/dev/null; then
    err "Docker is not installed. Nothing to remove."
fi

# ── Stopping container ───────────────────────────────────────────────────────
if [[ -d "$INSTALL_DIR" && -f "$INSTALL_DIR/docker-compose.yml" ]]; then
    info "Stopping and removing container..."
    cd "$INSTALL_DIR"
    docker compose down --remove-orphans || true
    ok "Container removed"
else
    # Fallback if no compose file found
    if docker ps -a --format '{{.Names}}' | grep -q '^minecraft$'; then
        info "Minecraft container found without compose file. Removing..."
        docker rm -f minecraft || true
        ok "Container removed (forced)"
    else
        info "No Minecraft container found."
    fi
fi

# ── Cleaning files ──────────────────────────────────────────────────────────
DELETE_DATA="${DELETE_DATA:-false}"

if [[ -d "$INSTALL_DIR" ]]; then
    if [[ "$DELETE_DATA" = "true" ]]; then
        info "Deleting data directory as requested (DELETE_DATA=true)..."
        rm -rf "$INSTALL_DIR"
        ok "Directory $INSTALL_DIR deleted"
    else
        info "Data directory preserved (use DELETE_DATA=true to remove)."
    fi
else
    info "Installation directory not found."
fi

hr
ok "Uninstallation complete."
hr
