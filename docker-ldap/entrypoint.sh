#!/bin/bash
# =============================================================================
# Entrypoint del contenedor OpenLDAP
# Primer arranque: configura slapd, carga módulos, crea estructura LDAP
# Arranques sucesivos: inicia slapd directamente con los volúmenes persistentes
# =============================================================================
set -e

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info() { echo -e "${GREEN}[LDAP]${NC} $1"; }
warn() { echo -e "${YELLOW}[LDAP]${NC} $1"; }
error() { echo -e "${RED}[LDAP]${NC} $1"; exit 1; }

# ── Validar variables obligatorias ────────────────────────────────────────────
[ -z "$LDAP_DOMAIN" ]         && error "Variable LDAP_DOMAIN no definida"
[ -z "$LDAP_ADMIN_PASSWORD" ] && error "Variable LDAP_ADMIN_PASSWORD no definida"
[ -z "$LDAP_READONLY_PASSWORD" ] && error "Variable LDAP_READONLY_PASSWORD no definida"

# ── Construir BASE_DN dinámicamente (soporta cualquier profundidad de dominio) ─
IFS='.' read -ra PARTS <<< "$LDAP_DOMAIN"
LDAP_BASE_DN=$(printf ",dc=%s" "${PARTS[@]}" | cut -c2-)
LDAP_ADMIN_DN="cn=admin,${LDAP_BASE_DN}"
LDAP_READONLY_DN="cn=readonly,${LDAP_BASE_DN}"

info "Dominio: ${LDAP_DOMAIN} → ${LDAP_BASE_DN}"

# ── Si ya está inicializado, arrancar directamente ────────────────────────────
if [ -f "/var/lib/ldap/data.mdb" ]; then
    info "Volumen ya inicializado — arrancando slapd..."
    exec /usr/sbin/slapd \
        -h "ldap:///" \
        -u openldap -g openldap \
        -F /etc/ldap/slapd.d \
        -d "${LDAP_LOG_LEVEL:-256}"
fi

# ══════════════════════════════════════════════════════════════════════════════
# PRIMER ARRANQUE: configuración completa
# ══════════════════════════════════════════════════════════════════════════════
info "Primer arranque — configurando slapd..."

# ── Configurar via debconf ────────────────────────────────────────────────────
debconf-set-selections << DEBCONF
slapd slapd/internal/generated_adminpw password ${LDAP_ADMIN_PASSWORD}
slapd slapd/internal/adminpw password ${LDAP_ADMIN_PASSWORD}
slapd slapd/password2 password ${LDAP_ADMIN_PASSWORD}
slapd slapd/password1 password ${LDAP_ADMIN_PASSWORD}
slapd slapd/domain string ${LDAP_DOMAIN}
slapd shared/organization string ${CENTER_NAME:-Mi Centro}
slapd slapd/backend string MDB
slapd slapd/purge_database boolean true
slapd slapd/move_old_database boolean true
slapd slapd/allow_ldap_v2 boolean false
slapd slapd/no_configuration boolean false
DEBCONF

dpkg-reconfigure -f noninteractive slapd 2>/dev/null
info "slapd configurado con dominio ${LDAP_DOMAIN} ✓"

# ── Arrancar slapd temporalmente para configuración vía OLC ──────────────────
/usr/sbin/slapd \
    -h "ldap://127.0.0.1/ ldapi:///" \
    -u openldap -g openldap \
    -F /etc/ldap/slapd.d
sleep 4

# ── Cargar schema ppolicy ─────────────────────────────────────────────────────
ldapadd -Y EXTERNAL -H ldapi:/// \
    -f /etc/ldap/schema/ppolicy.ldif 2>/dev/null \
    && info "Schema ppolicy cargado ✓" \
    || warn "Schema ppolicy ya existía"

# ── Cargar módulos: memberof, refint, ppolicy ─────────────────────────────────
ldapmodify -Y EXTERNAL -H ldapi:/// 2>/dev/null << LDIF || true
dn: cn=module{0},cn=config
changetype: modify
add: olcModuleLoad
olcModuleLoad: memberof
olcModuleLoad: refint
olcModuleLoad: ppolicy
LDIF
sleep 1
info "Módulos cargados ✓"

# ── Overlay memberof ──────────────────────────────────────────────────────────
ldapadd -Y EXTERNAL -H ldapi:/// 2>/dev/null << LDIF || true
dn: olcOverlay=memberof,olcDatabase={1}mdb,cn=config
objectClass: olcOverlayConfig
objectClass: olcMemberOf
olcOverlay: memberof
olcMemberOfRefInt: TRUE
olcMemberOfGroupOC: groupOfNames
olcMemberOfMemberAD: member
olcMemberOfMemberOfAD: memberOf
LDIF

# ── Overlay refint ────────────────────────────────────────────────────────────
ldapadd -Y EXTERNAL -H ldapi:/// 2>/dev/null << LDIF || true
dn: olcOverlay=refint,olcDatabase={1}mdb,cn=config
objectClass: olcOverlayConfig
objectClass: olcRefintConfig
olcOverlay: refint
olcRefintAttribute: memberOf member manager owner
LDIF

# ── Overlay ppolicy ───────────────────────────────────────────────────────────
ldapadd -Y EXTERNAL -H ldapi:/// 2>/dev/null << LDIF || true
dn: olcOverlay=ppolicy,olcDatabase={1}mdb,cn=config
objectClass: olcOverlayConfig
objectClass: olcPPolicyConfig
olcOverlay: ppolicy
olcPPolicyDefault: cn=default,ou=policies,${LDAP_BASE_DN}
olcPPolicyHashCleartext: TRUE
olcPPolicyUseLockout: FALSE
LDIF
info "Overlays configurados ✓"

# ── ACLs ──────────────────────────────────────────────────────────────────────
ldapmodify -Y EXTERNAL -H ldapi:/// 2>/dev/null << LDIF || true
dn: olcDatabase={1}mdb,cn=config
changetype: modify
replace: olcAccess
olcAccess: {0}to attrs=userPassword,pwdHistory,pwdFailureTime,pwdAccountLocked by self write by dn.exact="${LDAP_ADMIN_DN}" write by anonymous auth by * none
olcAccess: {1}to attrs=entry,objectClass,uid,cn,givenName,sn,mail,shadowExpire,memberOf by self read by dn.exact="${LDAP_ADMIN_DN}" write by dn.exact="${LDAP_READONLY_DN}" read by * none
olcAccess: {2}to * by dn.exact="${LDAP_ADMIN_DN}" write by * none
LDIF
info "ACLs configurados ✓"

# ── Estructura organizacional ─────────────────────────────────────────────────
ldapadd -x -D "$LDAP_ADMIN_DN" -w "$LDAP_ADMIN_PASSWORD" 2>/dev/null << LDIF || true
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

# ── Usuario readonly ──────────────────────────────────────────────────────────
READONLY_HASH=$(slappasswd -s "${LDAP_READONLY_PASSWORD}")
ldapadd -x -D "$LDAP_ADMIN_DN" -w "$LDAP_ADMIN_PASSWORD" 2>/dev/null << LDIF || true
dn: ${LDAP_READONLY_DN}
objectClass: simpleSecurityObject
objectClass: organizationalRole
cn: readonly
description: Usuario de solo lectura para autenticación
userPassword: ${READONLY_HASH}
LDIF

# ── Política de contraseñas por defecto ───────────────────────────────────────
ldapadd -x -D "$LDAP_ADMIN_DN" -w "$LDAP_ADMIN_PASSWORD" 2>/dev/null << LDIF || true
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
pwdMustChange: FALSE
LDIF

info "Estructura LDAP inicializada ✓"

# ── Parar slapd temporal ──────────────────────────────────────────────────────
SLAPD_PID=$(cat /var/run/slapd/slapd.pid 2>/dev/null || pgrep slapd || true)
[ -n "$SLAPD_PID" ] && kill "$SLAPD_PID" 2>/dev/null && sleep 2

info "================================================================"
info "  OpenLDAP listo: ${LDAP_BASE_DN}"
info "  Admin DN:       ${LDAP_ADMIN_DN}"
info "  Readonly DN:    ${LDAP_READONLY_DN}"
info "================================================================"

# ── Arrancar slapd en primer plano ────────────────────────────────────────────
exec /usr/sbin/slapd \
    -h "ldap:///" \
    -u openldap -g openldap \
    -F /etc/ldap/slapd.d \
    -d "${LDAP_LOG_LEVEL:-256}"
