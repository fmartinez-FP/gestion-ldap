#!/bin/bash
# =============================================================================
# Entrypoint — Gestor centralizado de usuarios LDAP
# Orden: validar env → esperar deps → bootstrap LDAP → migrar DB → cachear → supervisord
# =============================================================================
set -e

# ── Colores para logs ─────────────────────────────────────────────────────────
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[ENTRYPOINT]${NC} $1"; }
warn()  { echo -e "${YELLOW}[ENTRYPOINT]${NC} $1"; }
error() { echo -e "${RED}[ENTRYPOINT]${NC} $1"; exit 1; }

# ── Validar variables obligatorias ────────────────────────────────────────────
for VAR in LDAP_DOMAIN LDAP_ADMIN_PASSWORD LDAP_READONLY_PASSWORD DB_PASSWORD APP_KEY; do
    [ -z "${!VAR}" ] && error "Variable de entorno $VAR no definida. Revisa tu .env"
done

# ── Derivar LDAP_BASE_DN desde LDAP_DOMAIN ────────────────────────────────────
# Ej: ejemplo.es → dc=ejemplo,dc=es
LDAP_BASE_DN=$(echo "$LDAP_DOMAIN" | awk -F. '{printf "dc=%s", $1; for(i=2;i<=NF;i++) printf ",dc=%s", $i}')
LDAP_ADMIN_DN="cn=admin,${LDAP_BASE_DN}"
LDAP_READONLY_DN="cn=readonly,${LDAP_BASE_DN}"

export LDAP_BASE_DN LDAP_ADMIN_DN LDAP_READONLY_DN

info "Dominio LDAP: ${LDAP_DOMAIN} → ${LDAP_BASE_DN}"

# ── Poblar volumen public/ si está vacío (primer arranque) ────────────────────
if [ ! -f /var/www/html/public/index.php ]; then
    info "Primer arranque: poblando volumen public/..."
    cp -a /srv/public-seed/. /var/www/html/public/
    chown -R www-data:www-data /var/www/html/public
fi

# ── Esperar a MySQL ───────────────────────────────────────────────────────────
info "Esperando a MySQL en ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
RETRIES=30
until nc -z "${DB_HOST:-mysql}" "${DB_PORT:-3306}" 2>/dev/null; do
    RETRIES=$((RETRIES - 1))
    [ "$RETRIES" -le 0 ] && error "MySQL no disponible tras 30 intentos"
    sleep 2
done
info "MySQL listo ✓"

# ── Esperar a OpenLDAP ────────────────────────────────────────────────────────
info "Esperando a OpenLDAP en ${LDAP_HOST:-ldap}:${LDAP_PORT:-389}..."
RETRIES=30
until nc -z "${LDAP_HOST:-ldap}" "${LDAP_PORT:-389}" 2>/dev/null; do
    RETRIES=$((RETRIES - 1))
    [ "$RETRIES" -le 0 ] && error "OpenLDAP no disponible tras 30 intentos"
    sleep 2
done
# Dar unos segundos extra para que slapd termine su init interno
sleep 3
info "OpenLDAP listo ✓"

# ── Bootstrap de estructura LDAP (idempotente) ────────────────────────────────
info "Comprobando estructura LDAP..."

ldap_exists() {
    ldapsearch -x \
        -H "ldap://${LDAP_HOST:-ldap}:${LDAP_PORT:-389}" \
        -D "${LDAP_ADMIN_DN}" \
        -w "${LDAP_ADMIN_PASSWORD}" \
        -b "$1" -s base "(objectClass=*)" dn 2>/dev/null | grep -q "^dn:"
}

if ! ldap_exists "ou=users,${LDAP_BASE_DN}"; then
    info "Creando estructura organizacional LDAP..."

    ldapadd -x \
        -H "ldap://${LDAP_HOST:-ldap}:${LDAP_PORT:-389}" \
        -D "${LDAP_ADMIN_DN}" \
        -w "${LDAP_ADMIN_PASSWORD}" <<LDIF
dn: ou=users,${LDAP_BASE_DN}
objectClass: organizationalUnit
ou: users
description: Usuarios del sistema

dn: ou=groups,${LDAP_BASE_DN}
objectClass: organizationalUnit
ou: groups
description: Grupos de aplicaciones

dn: ou=policies,${LDAP_BASE_DN}
objectClass: organizationalUnit
ou: policies
description: Políticas de contraseñas
LDIF

    info "Estructura LDAP creada ✓"
else
    info "Estructura LDAP ya existe ✓"
fi

# ── Política de contraseñas por defecto (permisiva, configurable) ─────────────
if ! ldap_exists "cn=default,ou=policies,${LDAP_BASE_DN}"; then
    info "Creando política de contraseñas por defecto..."
    ldapadd -x \
        -H "ldap://${LDAP_HOST:-ldap}:${LDAP_PORT:-389}" \
        -D "${LDAP_ADMIN_DN}" \
        -w "${LDAP_ADMIN_PASSWORD}" <<LDIF
dn: cn=default,ou=policies,${LDAP_BASE_DN}
objectClass: top
objectClass: person
objectClass: pwdPolicy
cn: default
sn: default
pwdAttribute: userPassword
pwdMinLength: 8
pwdMaxAge: 0
pwdInHistory: 0
pwdCheckQuality: 0
pwdLockout: FALSE
pwdMaxFailure: 0
pwdExpireWarning: 0
pwdGraceAuthNLimit: 0
pwdMustChange: FALSE
LDIF
    info "Política de contraseñas creada ✓"
fi

# ── Migraciones de base de datos ──────────────────────────────────────────────
info "Ejecutando migraciones..."
cd /var/www/html
php artisan migrate --force --no-interaction
info "Migraciones completadas ✓"

# ── Seeder (solo en primera instalación si la tabla admins está vacía) ─────────
ADMIN_COUNT=$(php artisan tinker --no-interaction 2>/dev/null \
    --execute="echo \App\Models\Admin::count();" 2>/dev/null \
    | tail -1 | tr -d '[:space:]')

if [ "$ADMIN_COUNT" = "0" ] || [ -z "$ADMIN_COUNT" ]; then
    info "Base de datos vacía, ejecutando seeder inicial..."
    php artisan db:seed --force --no-interaction
    info "Seeder ejecutado ✓"
else
    info "Base de datos ya inicializada, omitiendo seeder ✓"
fi

# ── Cachear configuración Laravel ─────────────────────────────────────────────
info "Optimizando aplicación..."
php artisan config:cache  --no-interaction
php artisan route:cache   --no-interaction
php artisan view:cache    --no-interaction
php artisan event:cache   --no-interaction
info "Optimización completada ✓"

# ── Permisos finales de storage ───────────────────────────────────────────────
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

info "==============================================="
info " Gestor LDAP iniciado correctamente"
info " URL: ${APP_URL:-http://localhost:${EXTERNAL_PORT:-8083}}"
info "==============================================="

# ── Arrancar supervisord (nginx + php-fpm) ────────────────────────────────────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
