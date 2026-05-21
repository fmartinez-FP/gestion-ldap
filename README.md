# Gestor centralizado de usuarios LDAP

Panel de administración web para gestionar usuarios LDAP de forma centralizada en centros educativos. Permite crear, editar y eliminar usuarios, asignarlos a grupos de aplicaciones, y ofrecer a los propios usuarios un portal para cambiar su contraseña.

Desarrollado con **Laravel 12 + Livewire**, completamente dockerizado y listo para desplegar en cualquier servidor Linux.

---

## ¿Qué incluye?

- **Panel de administración** — gestión completa de usuarios y grupos LDAP
- **Portal de usuario** — los usuarios pueden cambiar su contraseña sin intervención del administrador
- **Importación masiva** — carga de usuarios desde CSV
- **Exportación LDIF** — backup de usuarios en formato estándar
- **Registro de auditoría** — historial de todas las operaciones
- **Wizard de primer arranque** — configura el nombre del centro, logo y contraseña admin en el primer acceso
- **Servidor OpenLDAP incluido** — imagen propia basada en Ubuntu 22.04, compatible con Proxmox LXC

---

## Arquitectura

```
[Nginx Proxy Manager]  ←── SSL externo (recomendado)
         │
    ┌────▼──────────────────────┐
    │  gestion-ldap-app         │  nginx + PHP-FPM 8.2
    │  Puerto: 8083             │  Laravel 12 + Livewire
    └────┬──────────┬───────────┘
         │          │
    ┌────▼───┐  ┌───▼──────────┐
    │ MySQL  │  │  OpenLDAP    │
    │  8.0   │  │  2.5 (U22)   │
    └────────┘  └──────────────┘
```

Los tres servicios corren en una red Docker interna aislada. OpenLDAP expone el puerto 389 en el host para que otras aplicaciones del servidor puedan autenticarse contra él.

---

## Requisitos

- Docker Engine 24.x o superior
- Docker Compose v2.x
- 1 GB de RAM disponible
- Puerto libre en el host (por defecto: 8083)

---

## Instalación rápida

```bash
# 1. Clonar el repositorio
git clone https://github.com/TU_USUARIO/gestion-ldap.git
cd gestion-ldap

# 2. Configurar el entorno
cp .env.example .env
nano .env   # Rellena CENTER_NAME, LDAP_DOMAIN, contraseñas, SMTP y APP_URL

# 3. Construir y arrancar
docker compose build
docker compose up -d

# 4. Verificar
docker compose ps
```

Accede a la URL configurada en `APP_URL`. En el primer acceso aparecerá el **wizard de configuración inicial**.

Para instrucciones detalladas, resolución de problemas y configuración avanzada consulta [INSTALL.md](INSTALL.md).

---

## Configuración mínima del `.env`

```env
CENTER_NAME="IES Nombre del Centro"
APP_URL=https://gestion.tucentro.es
EXTERNAL_PORT=8083

DB_PASSWORD=ContraseñaSegura!
DB_ROOT_PASSWORD=OtraContraseña!

LDAP_DOMAIN=tucentro.es
LDAP_ADMIN_PASSWORD=AdminLDAP!
LDAP_READONLY_PASSWORD=ReadOnly!

MAIL_HOST=smtp.tucentro.es
MAIL_PORT=587
MAIL_USERNAME=gestion@tucentro.es
MAIL_PASSWORD=ContraseñaSMTP!
MAIL_FROM_ADDRESS=gestion@tucentro.es
```

---

## Compatibilidad con Proxmox LXC

Si el servidor es un contenedor LXC en Proxmox, se necesita configuración adicional en el host Proxmox y usar el runtime `crun` en lugar de `runc`. El proceso completo está documentado en [INSTALL.md — Sección 7](INSTALL.md#7-redes-con-acceso-restringido-a-internet).

---

## Conectar otras aplicaciones al LDAP

El servidor OpenLDAP queda accesible en el host en el puerto configurado (`LDAP_EXPOSE_PORT`, por defecto 389). Configura tus aplicaciones con:

```
Host:          IP_DEL_SERVIDOR (o 127.0.0.1 si está en el mismo host)
Puerto:        389
Base DN:       dc=tucentro,dc=es
Bind DN:       cn=readonly,dc=tucentro,dc=es
Bind Password: valor de LDAP_READONLY_PASSWORD
```

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Livewire 3, Tailwind CSS, Vite |
| Base de datos | MySQL 8.0 |
| Directorio | OpenLDAP 2.5 (Ubuntu 22.04) |
| Servidor web | nginx 1.x + PHP-FPM |
| Contenedores | Docker + Docker Compose v2 |

---

## Licencia

MIT — libre para usar, modificar y distribuir en centros educativos y otras organizaciones.
