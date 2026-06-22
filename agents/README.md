# RawHost — API de Notificaciones y Métricas

Base URL: `https://rawhost.net`

Autenticación mediante header `X-API-KEY` (genera tu clave desde el perfil).

---

## Arquitectura

Las métricas se obtienen directamente desde SolusVM — no necesitas instalar nada en la VPS.

```
rawhost.net
  ├── api/agent/last_metrics  → consulta métricas a SolusVM + push Gotify (bajo demanda)
  ├── api/agent/config        → lee/escribe umbrales de alerta por VPS
  ├── agents/notify_expiring  → cron hourly: alertas de expiración
  └── agents/notify_metrics   → cron cada 15 min: alertas de CPU/RAM/Disco
```

---

## 1. Obtener métricas en tiempo real

Consulta CPU, RAM, disco y red de la VPS vía SolusVM. Si tienes Gotify configurado, recibirás un push de resumen en el momento.

```bash
curl -s \
  -H "X-API-KEY: 840bed7102075b7a01f1d7cfd3fd63eafe9ae7daa6def32c" \
  "https://rawhost.net/api/agent/last_metrics?vps_id=12"
```

**Respuesta:**
```json
{
  "success": true,
  "vps_id": 12,
  "source": "solusvm",
  "summary": {
    "cpu_pct": 42.5,
    "ram_pct": 61.2,
    "disk_pct": 33.0,
    "net_rx": 12.4,
    "net_tx": 3.1
  },
  "alerts": {
    "cpu_threshold": 90,
    "ram_threshold": 90,
    "disk_threshold": 85
  },
  "raw": {
    "cpu": [...],
    "memory": [...],
    "network": [...],
    "disks": { "used": 33, "total": 100 }
  }
}
```

También incluye un push Gotify urgente si la VPS expira en menos de 48 horas.

---

## 2. Ver configuración de alertas

```bash
curl -s \
  -H "X-API-KEY: 840bed7102075b7a01f1d7cfd3fd63eafe9ae7daa6def32c" \
  "https://rawhost.net/api/agent/config?vps_id=12"
```

**Respuesta:**
```json
{
  "success": true,
  "config": {
    "interval_s": 60,
    "metrics": ["cpu", "ram", "disk", "network"],
    "alerts": {
      "cpu_threshold": 90,
      "ram_threshold": 90,
      "disk_threshold": 85
    }
  }
}
```

---

## 3. Actualizar umbrales de alerta

Ajusta los porcentajes a partir de los cuales se dispara una alerta Gotify.

```bash
curl -s -X PUT \
  -H "X-API-KEY: 840bed7102075b7a01f1d7cfd3fd63eafe9ae7daa6def32c" \
  -H "Content-Type: application/json" \
  -d '{
    "vps_id": 12,
    "interval_s": 30,
    "metrics": ["cpu", "ram", "disk", "network"],
    "alerts": {
      "cpu_threshold": 85,
      "ram_threshold": 80,
      "disk_threshold": 90
    }
  }' \
  "https://rawhost.net/api/agent/config"
```

**Respuesta:**
```json
{ "success": true }
```

---

## Crons en el servidor

| Archivo | Horario | Función |
|---------|---------|---------|
| `agents/notify_expiring.php` | `0 * * * *` (cada hora) | Alertas de expiración a 7d, 3d, 1d |
| `agents/notify_metrics.php`  | `*/15 * * * *` (cada 15 min) | Alertas de CPU/RAM/Disco si superan umbrales |

Registrar en cron:
```bash
0 * * * *    php /var/www/veneko/agents/notify_expiring.php >> /var/log/veneko/expiring.log 2>&1
*/15 * * * * php /var/www/veneko/agents/notify_metrics.php >> /var/log/veneko/metrics.log 2>&1
```

---

## Prioridades de alerta (Gotify)

| Evento | Prioridad |
|--------|-----------|
| Resumen de métricas (bajo demanda) | 3 (info) |
| Expiración en 7 días | 4 (baja) |
| Expiración en 3 días | 6 (media) |
| CPU / RAM / Disco ≥ umbral | 9 (alta) |
| Expiración en 1 día o < 48 h | 10 (urgente) |
