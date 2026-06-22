# Daemons

Este directorio contiene scripts daemon que se ejecutan en segundo plano para mantener sincronizada la plataforma.

## Daemons Disponibles

### 1. vps_expire_daemon.php
Monitorea y suspende VPS que han expirado.

**Función:**
- Busca VPS activos que hayan pasado su fecha de expiración
- Actualiza su estado a SUSPENDED
- Se ejecuta cada 60 segundos

**Uso:**
```bash
php /var/www/veneko/daemon/vps_expire_daemon.php
```

### 2. ip_value_daemon.php
Sincroniza las direcciones IP asignadas desde la API externa.

**Función:**
- Busca addons (IPs) que no tienen valor asignado localmente
- Consulta la API externa para obtener la IP asignada
- Hace match por `external_id` y copia el campo `assigned_value` al campo `value`
- Actualiza el estado del addon a ACTIVE si estaba en PENDING
- Se ejecuta cada 30 segundos

**Uso:**
```bash
php /var/www/veneko/daemon/ip_value_daemon.php
```

## Ejecutar en Segundo Plano

Para ejecutar los daemons en segundo plano de forma permanente, puedes usar `nohup`:

```bash
# VPS Expiration Daemon
nohup php /var/www/veneko/daemon/vps_expire_daemon.php > /var/www/veneko/logs/vps_expire.log 2>&1 &

# IP Value Sync Daemon
nohup php /var/www/veneko/daemon/ip_value_daemon.php > /var/www/veneko/logs/ip_value.log 2>&1 &
```

## Usando systemd (Recomendado)

Para una solución más robusta, puedes crear servicios systemd:

### vps_expire.service
```ini
[Unit]
Description=VPS Expiration Daemon
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/veneko
ExecStart=/usr/bin/php /var/www/veneko/daemon/vps_expire_daemon.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### ip_value_sync.service
```ini
[Unit]
Description=IP Value Sync Daemon
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/veneko
ExecStart=/usr/bin/php /var/www/veneko/daemon/ip_value_daemon.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Instalación de servicios:**
```bash
# Copiar los archivos de servicio
sudo cp vps_expire.service /etc/systemd/system/
sudo cp ip_value_sync.service /etc/systemd/system/

# Recargar systemd
sudo systemctl daemon-reload

# Habilitar los servicios
sudo systemctl enable vps_expire.service
sudo systemctl enable ip_value_sync.service

# Iniciar los servicios
sudo systemctl start vps_expire.service
sudo systemctl start ip_value_sync.service

# Ver el estado
sudo systemctl status vps_expire.service
sudo systemctl status ip_value_sync.service
```

## Logs

Los logs se mostrarán en la salida estándar. Si usas systemd, puedes ver los logs con:

```bash
sudo journalctl -u vps_expire.service -f
sudo journalctl -u ip_value_sync.service -f
```
