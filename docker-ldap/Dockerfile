FROM ubuntu:22.04

LABEL maintainer="IES Pacífico" \
      description="OpenLDAP 2.5 para Gestor centralizado de usuarios LDAP" \
      version="1.0"

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Madrid

# Instalar slapd y herramientas (misma versión que el servidor de producción)
RUN apt-get update && apt-get install -y --no-install-recommends \
    slapd \
    ldap-utils \
    libsasl2-modules \
    tzdata \
    && rm -rf /var/lib/apt/lists/*

# Volúmenes para persistencia (datos y configuración OLC)
VOLUME ["/var/lib/ldap", "/etc/ldap/slapd.d"]

COPY entrypoint.sh /usr/local/bin/docker-ldap-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-ldap-entrypoint.sh

EXPOSE 389

HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=5 \
    CMD ldapsearch -x -H ldap://127.0.0.1/ -b "" -s base "(objectClass=*)" > /dev/null 2>&1

ENTRYPOINT ["/usr/local/bin/docker-ldap-entrypoint.sh"]
