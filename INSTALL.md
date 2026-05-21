# Gestor centralizado de usuarios LDAP — Guía de instalación

Paquete autocontenido para centros educativos. Incluye panel web, base de datos MySQL y servidor OpenLDAP, todo en contenedores Docker.

---

## Índice
1. [Requisitos](#1-requisitos)
2. [Instalación](#2-instalación)
3. [Configurar Nginx Proxy Manager](#3-configurar-nginx-proxy-manager)
4. [Wizard de primer arranque](#4-wizard-de-primer-arranque)
5. [Añadir aplicaciones al panel](#5-añadir-aplicaciones-al-panel)
6. [Conectar tus apps al servidor LDAP](#6-conectar-tus-apps-al-servidor-ldap)
7. [Redes con acceso restringido a internet](#7-redes-con-acceso-restringido-a-internet)
8. [Operaciones habituales](#8-operaciones-habituales)
9. [Backups](#9-backups)

---

## 1. Requisitos

| Requisito | Mínimo |
|---|---|
| Docker Engine | 24.x o superior |
| Docker Compose | v2.x o superior |
| RAM | 1 GB disponible |
| Disco | 5 GB disponibles |
| SO | Linux (Ubuntu 22.04 recomendado) |
| Puerto libre | Configurable (por defecto 8083) |

```bash
# Instalar Docker si no está instalado
curl -fsSL https://get.docker.com | bash
systemctl enable --now docker
```

> **Proxmox LXC:** Si tu servidor es un contenedor LXC en Proxmox, lee el apartado [7. Redes con acceso restringido](#7-redes-con-acceso-restringido-a-internet) antes de continuar.

---

## 2. Instalación

### 2.1 Descomprimir
```bash
unzip gestion-ldap-dist-*.zip
cd gestion-ldap-dist-*/
```

### 2.2 Configurar el entorno
```bash
cp .env.example .env
nano .env   # Rellena TODOS los valores marcados como obligatorios
```

Variables obligatorias:

```env
CENTER_NAME="IES Nombre del Centro"   # Nombre de tu centro

APP_URL=https://gestion.tucentro.es   # URL pública del panel
EXTERNAL_PORT=8083                    # Puerto que expondrá Docker

DB_PASSWORD=ContraseñaSegura!         # Para MySQL del panel
DB_ROOT_PASSWORD=OtraContraseña!      # Root MySQL (uso interno)

LDAP_DOMAIN=tucentro.es               # Tu dominio → dc=tucentro,dc=es
LDAP_ADMIN_PASSWORD=AdminLDAP!        # Contraseña admin LDAP
LDAP_READONLY_PASSWORD=ReadOnly!      # Contraseña usuario readonly

MAIL_HOST=smtp.tucentro.es
MAIL_PORT=587
MAIL_USERNAME=gestion@tucentro.es
MAIL_PASSWORD=ContraseñaSMTP!
MAIL_FROM_ADDRESS=gestion@tucentro.es
```

> Guarda `.env` con permisos restrictivos: `chmod 600 .env`

### 2.3 Construir y arrancar
```bash
# Construir las 3 imágenes (tarda 3-8 min la primera vez)
docker compose build

# Arrancar en segundo plano
docker compose up -d

# Verificar que los 3 contenedores están sanos
docker compose ps
```

Los tres contenedores deben aparecer como `running` o `healthy`:
```
NAME                    STATUS
gestion-ldap-app        running (healthy)
gestion-ldap-mysql      running (healthy)
gestion-ldap-openldap   running (healthy)
```

---

## 3. Configurar Nginx Proxy Manager

En tu Nginx Proxy Manager crea un Proxy Host:

| Campo | Valor |
|---|---|
| Domain Names | `gestion.tucentro.es` |
| Scheme | `http` |
| Forward Hostname / IP | IP del servidor Docker |
| Forward Port | `8083` (o tu `EXTERNAL_PORT`) |
| SSL | Activar + Let's Encrypt |
| Force SSL | Activar |
| Websockets Support | **Activar** (necesario para Livewire) |

---

## 4. Wizard de primer arranque

Al acceder por primera vez, el sistema muestra automáticamente el wizard de configuración:

**Paso 1 — Centro:** Nombre del centro + logo (PNG/JPG, máx. 2MB).

**Paso 2 — Administrador:** Cambia la contraseña por defecto. Usa una contraseña segura — esta es la cuenta que gestiona todos los usuarios LDAP.

**Paso 3 — Correo:** Test de envío SMTP. Puedes omitirlo y configurarlo después editando el `.env`.

**Paso 4 — Listo:** El panel queda operativo.

---

## 5. Añadir aplicaciones al panel

El panel gestiona qué aplicaciones web tienen acceso a cada usuario LDAP. Para añadir una aplicación:

### Desde el panel de administración
1. Accede al panel → sección **Aplicaciones**
2. Haz clic en **Nueva aplicación**
3. Rellena: nombre, URL, descripción, icono (opcional)
4. Guarda

### Subir un icono para la aplicación
Los iconos se almacenan en `public/icons/`. Para añadir uno:

```bash
# Copiar el icono al volumen del contenedor
docker cp /ruta/local/mi-app.png gestion-ldap-app:/var/www/html/public/icons/mi-app.png

# En el panel, usa el nombre relativo: icons/mi-app.png
```

Formatos recomendados: PNG con fondo transparente, 64×64 px o mayor.

### Grupos LDAP por aplicación
Cada aplicación puede tener un grupo LDAP asociado. Cuando creas/editas una aplicación en el panel puedes asignarle un grupo. Solo los usuarios que pertenezcan a ese grupo tendrán acceso a esa aplicación.

Para crear el grupo LDAP desde el panel: ve a **Usuarios** → selecciona un usuario → edita sus grupos. Los grupos se crean automáticamente al asignarlos por primera vez.

---

## 6. Conectar tus apps al servidor LDAP

El servidor LDAP Docker expone el puerto configurado en `.env` (`LDAP_EXPOSE_PORT`, por defecto 389) en el host. Tus aplicaciones web (ffe, guardias, inventarios, etc.) deben apuntar a:

```
Host LDAP:    IP_DEL_SERVIDOR_DOCKER (o 127.0.0.1 si están en el mismo servidor)
Puerto LDAP:  389 (o el valor de LDAP_EXPOSE_PORT)
Base DN:      dc=tucentro,dc=es  (derivado de LDAP_DOMAIN=tucentro.es)
Bind DN:      cn=readonly,dc=tucentro,dc=es
Bind Password: valor de LDAP_READONLY_PASSWORD en .env
```

> Si tus apps están en el **mismo servidor** que Docker, usa `LDAP_BIND_INTERFACE=127.0.0.1` en `.env`.
> Si están en **servidores diferentes**, usa `LDAP_BIND_INTERFACE=0.0.0.0` y asegúrate de que el firewall permite el puerto 389 desde los servidores necesarios.

---

## 7. Redes con acceso restringido a internet

Algunos centros tienen acceso a internet filtrado (Fastly CDN y Cloudflare R2 bloqueados). Si `docker compose build` falla al descargar imágenes:

### Configurar mirror de Docker
```bash
cat > /etc/docker/daemon.json << 'EOF'
{
  "registry-mirrors": ["https://mirror.gcr.io", "https://dockerhub.timeweb.cloud"]
}
EOF
systemctl restart docker
```

### Proxmox LXC
Si el servidor es un contenedor LXC en Proxmox y los contenedores Docker no arrancan con el error `net.ipv4.ip_unprivileged_port_start: permission denied`:

1. En el **host Proxmox**, edita la config del contenedor (sustituye `104` por tu CTID):
```bash
echo "lxc.apparmor.profile: unconfined" >> /etc/pve/lxc/104.conf
echo "lxc.cap.drop:" >> /etc/pve/lxc/104.conf
echo "lxc.seccomp.profile:" >> /etc/pve/lxc/104.conf
pct stop 104 && pct start 104
```

2. Instala el runtime `crun` (versión 1.x):
```bash
curl -L "https://github.com/containers/crun/releases/download/1.14.4/crun-1.14.4-linux-amd64" \
     -o /usr/local/bin/crun && chmod +x /usr/local/bin/crun
```

3. Configura Docker para usar crun:
```bash
# Añade al daemon.json existente:
# "runtimes": {"crun": {"path": "/usr/local/bin/crun"}},
# "default-runtime": "crun"
systemctl restart docker
```

4. Descomenta las líneas `# runtime: crun` en `docker-compose.yml`.

---

## 8. Operaciones habituales

### Ver logs
```bash
docker compose logs -f app    # Logs del panel
docker compose logs -f ldap   # Logs de OpenLDAP
docker compose logs -f mysql  # Logs de MySQL
```

### Reiniciar un servicio
```bash
docker compose restart app
```

### Actualizar el panel (nuevo código)
```bash
docker compose build app
docker compose up -d app
```

### Acceder a la consola de la app
```bash
docker exec -it gestion-ldap-app bash
php artisan tinker
```

### Parar todo (sin borrar datos)
```bash
docker compose down
```

---

## 9. Backups

```bash
#!/bin/bash
# Guardar como /opt/backup-ldap.sh y añadir a cron: 0 2 * * *

FECHA=$(date +%Y-%m-%d)
DIR=/opt/backups/$FECHA
mkdir -p "$DIR"

# MySQL
docker exec gestion-ldap-mysql \
    mysqldump -u gestion_user -p"${DB_PASSWORD}" gestion_ldap \
    | gzip > "$DIR/mysql_$FECHA.sql.gz"

# LDAP (usuarios y grupos)
LDAP_BASE_DN="dc=$(echo $LDAP_DOMAIN | cut -d. -f1),dc=$(echo $LDAP_DOMAIN | cut -d. -f2)"
docker exec gestion-ldap-openldap \
    ldapsearch -x \
    -D "cn=admin,$LDAP_BASE_DN" -w "$LDAP_ADMIN_PASSWORD" \
    -b "ou=users,$LDAP_BASE_DN" "(objectClass=inetOrgPerson)" \
    | gzip > "$DIR/ldap_$FECHA.ldif.gz"

echo "Backup completado: $DIR"
```
