#!/bin/bash
# RawHost VPS Agent
# Instalación: sudo bash vps_agent.sh install
# Config en: /etc/rawhost-agent/agent.conf

CONF_FILE="/etc/rawhost-agent/agent.conf"
SERVICE_FILE="/etc/systemd/system/rawhost-agent.service"
AGENT_SCRIPT="/usr/local/bin/rawhost-agent"

# ── Install ──────────────────────────────────────────────────────────────────
if [[ "$1" == "install" ]]; then
    read -rp "API Key: " API_KEY
    read -rp "VPS ID: " VPS_ID
    read -rp "Server URL [https://rawhost.net]: " SERVER_URL
    SERVER_URL="${SERVER_URL:-https://rawhost.net}"

    mkdir -p /etc/rawhost-agent
    cat > "$CONF_FILE" <<EOF
API_KEY=$API_KEY
VPS_ID=$VPS_ID
SERVER_URL=$SERVER_URL
EOF
    chmod 600 "$CONF_FILE"

    cp "$0" "$AGENT_SCRIPT"
    chmod +x "$AGENT_SCRIPT"

    cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=RawHost VPS Agent
After=network.target

[Service]
ExecStart=$AGENT_SCRIPT run
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    systemctl enable rawhost-agent
    systemctl start rawhost-agent
    echo "Agente instalado y corriendo."
    exit 0
fi

# ── Run loop ─────────────────────────────────────────────────────────────────
if [[ "$1" == "run" ]]; then
    source "$CONF_FILE"
    INTERVAL=60  # default, overridden by server config

    while true; do
        # Pull config from server
        CONFIG=$(curl -sf -H "X-API-KEY: $API_KEY" "$SERVER_URL/api/agent/config?vps_id=$VPS_ID" 2>/dev/null)
        if [[ $? -eq 0 ]]; then
            NEW_INTERVAL=$(echo "$CONFIG" | grep -o '"interval_s":[0-9]*' | grep -o '[0-9]*')
            [[ -n "$NEW_INTERVAL" ]] && INTERVAL=$NEW_INTERVAL
        fi

        # Collect metrics
        CPU_PCT=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | tr -d '%us,' || echo 0)
        RAM_TOTAL=$(grep MemTotal /proc/meminfo | awk '{print $2}')
        RAM_FREE=$(grep MemAvailable /proc/meminfo | awk '{print $2}')
        RAM_PCT=$(awk "BEGIN {printf \"%.1f\", (($RAM_TOTAL - $RAM_FREE) / $RAM_TOTAL) * 100}")
        DISK_PCT=$(df / | awk 'NR==2{gsub(/%/,""); print $5}')

        # Network (rx/tx delta in KB over 1s sample)
        IFACE=$(ip route | grep default | awk '{print $5}' | head -1)
        RX1=$(cat /sys/class/net/$IFACE/statistics/rx_bytes 2>/dev/null || echo 0)
        TX1=$(cat /sys/class/net/$IFACE/statistics/tx_bytes 2>/dev/null || echo 0)
        sleep 1
        RX2=$(cat /sys/class/net/$IFACE/statistics/rx_bytes 2>/dev/null || echo 0)
        TX2=$(cat /sys/class/net/$IFACE/statistics/tx_bytes 2>/dev/null || echo 0)
        NET_RX_KB=$(awk "BEGIN {printf \"%.2f\", ($RX2 - $RX1) / 1024}")
        NET_TX_KB=$(awk "BEGIN {printf \"%.2f\", ($TX2 - $TX1) / 1024}")

        # Send metrics
        curl -sf -X POST "$SERVER_URL/api/agent/metrics" \
            -H "X-API-KEY: $API_KEY" \
            -H "Content-Type: application/json" \
            -d "{\"vps_id\":$VPS_ID,\"cpu_pct\":$CPU_PCT,\"ram_pct\":$RAM_PCT,\"disk_pct\":$DISK_PCT,\"net_rx_kb\":$NET_RX_KB,\"net_tx_kb\":$NET_TX_KB}" \
            > /dev/null 2>&1

        sleep $((INTERVAL - 1))
    done
fi

echo "Uso: $0 install | run"
