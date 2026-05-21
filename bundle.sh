#!/bin/bash
# =============================================================================
# bundle.sh — Genera el paquete distribuible del Gestor LDAP
#
# Ejecutar desde la raíz del proyecto: bash bundle.sh
#
# Genera: ~/gestion-ldap-dist-YYYYMMDD.zip
# El ZIP contiene todo lo necesario para que otro centro levante
# el sistema con: docker compose build && docker compose up -d
# =============================================================================
set -e

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info() { echo -e "${GREEN}[BUNDLE]${NC} $1"; }
warn() { echo -e "${YELLOW}[BUNDLE]${NC} $1"; }

[ ! -f "artisan" ] && echo "Ejecuta desde la raíz del proyecto" && exit 1
[ ! -f "app/Models/Setting.php" ] && echo "Ejecuta primero patches/apply-patches.sh" && exit 1

VERSION=$(date +%Y%m%d)
BUNDLE_NAME="gestion-ldap-dist-${VERSION}"
TEMP_DIR="/tmp/${BUNDLE_NAME}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

info "Preparando paquete distribuible v${VERSION}..."

# ── Compilar assets si no están ───────────────────────────────────────────────
if [ ! -f "public/build/manifest.json" ]; then
    info "Compilando assets frontend..."
    npm config set registry https://registry.npmmirror.com 2>/dev/null || true
    npm install --silent
    npm run build
fi

# Generar package-lock.json si no existe
if [ ! -f "package-lock.json" ]; then
    info "Generando package-lock.json..."
    npm install --package-lock-only --silent 2>/dev/null || true
fi

# ── Crear directorio temporal ─────────────────────────────────────────────────
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR"

info "Copiando ficheros del proyecto..."

# ── Copiar código fuente (excluir ficheros de desarrollo/runtime) ─────────────
rsync -a \
    --exclude='.git/' \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='.env' \
    --exclude='*.sqlite' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*.php' \
    --exclude='bootstrap/cache/config.php' \
    --exclude='bootstrap/cache/routes*' \
    --exclude='bootstrap/cache/services.php' \
    --exclude='bootstrap/cache/packages.php' \
    --exclude='gestion-ldap-docker*/' \
    --exclude='patches/' \
    --exclude='migrate-to-docker.sh' \
    --exclude='Favicon.png' \
    --exclude='public/icons/velxio.png' \
    . "$TEMP_DIR/"

# ── Copiar docker-ldap/ (imagen LDAP personalizada) ───────────────────────────
info "Añadiendo imagen LDAP personalizada..."
mkdir -p "$TEMP_DIR/docker-ldap"

# Buscar docker-ldap/ en el directorio del script o en /root
if [ -d "${SCRIPT_DIR}/docker-ldap" ]; then
    cp -r "${SCRIPT_DIR}/docker-ldap/." "$TEMP_DIR/docker-ldap/"
elif [ -d "/root/gestion-ldap-opt-d/docker-ldap" ]; then
    cp -r /root/gestion-ldap-opt-d/docker-ldap/. "$TEMP_DIR/docker-ldap/"
else
    warn "docker-ldap/ no encontrado. Colócalo en ${SCRIPT_DIR}/docker-ldap/ y re-ejecuta."
fi

# ── Usar Dockerfile de distribución (con node-builder y npm mirror) ────────────
if [ -f "${SCRIPT_DIR}/Dockerfile.dist" ]; then
    cp "${SCRIPT_DIR}/Dockerfile.dist" "$TEMP_DIR/Dockerfile"
elif [ -f "/root/gestion-ldap-opt-d/Dockerfile.dist" ]; then
    cp /root/gestion-ldap-opt-d/Dockerfile.dist "$TEMP_DIR/Dockerfile"
fi

# ── Usar docker-compose.yml de distribución (incluye contenedor LDAP) ─────────
if [ -f "${SCRIPT_DIR}/docker-compose.dist.yml" ]; then
    cp "${SCRIPT_DIR}/docker-compose.dist.yml" "$TEMP_DIR/docker-compose.yml"
elif [ -f "/root/gestion-ldap-opt-d/docker-compose.dist.yml" ]; then
    cp /root/gestion-ldap-opt-d/docker-compose.dist.yml "$TEMP_DIR/docker-compose.yml"
fi

# ── .env.example actualizado ──────────────────────────────────────────────────
if [ -f "/root/gestion-ldap-opt-d/.env.dist.example" ]; then
    cp /root/gestion-ldap-opt-d/.env.dist.example "$TEMP_DIR/.env.example"
fi

# ── INSTALL.md actualizado ────────────────────────────────────────────────────
if [ -f "/root/gestion-ldap-opt-d/INSTALL.md" ]; then
    cp /root/gestion-ldap-opt-d/INSTALL.md "$TEMP_DIR/INSTALL.md"
fi

# ── Limpiar ficheros específicos de IES Pacífico ──────────────────────────────
rm -f "$TEMP_DIR/public/images/logo.png"     # el wizard pedirá el logo del nuevo centro
rm -f "$TEMP_DIR/public/favicon.png"
rm -f "$TEMP_DIR/Favicon.png"
rm -f "$TEMP_DIR/README.md"

# ── Permisos correctos en entrypoints ─────────────────────────────────────────
chmod +x "$TEMP_DIR/docker/entrypoint.sh" 2>/dev/null || true
chmod +x "$TEMP_DIR/docker-ldap/entrypoint.sh" 2>/dev/null || true

# ── Crear ZIP ─────────────────────────────────────────────────────────────────
info "Empaquetando..."
cd /tmp
zip -r "${BUNDLE_NAME}.zip" "${BUNDLE_NAME}/" \
    --exclude "*.DS_Store" \
    --exclude "__MACOSX" \
    -q

mv "${BUNDLE_NAME}.zip" ~/
rm -rf "$TEMP_DIR"

SIZE=$(du -sh ~/"${BUNDLE_NAME}.zip" | cut -f1)
info "═══════════════════════════════════════════════════"
info "  Paquete generado: ~/${BUNDLE_NAME}.zip (${SIZE})"
info "═══════════════════════════════════════════════════"
info ""
info "Para distribuir a otro centro:"
info "  1. Envía el ZIP"
info "  2. El centro sigue las instrucciones de INSTALL.md"
info "  3. Básicamente: descomprimir → rellenar .env → docker compose build → up"
