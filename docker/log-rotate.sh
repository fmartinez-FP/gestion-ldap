#!/bin/sh
# Rotación simple de los logs de nginx (el contenedor no tiene cron ni logrotate).
# Cuando un log supera LOG_MAX_MB lo renombra a .1, pide a nginx que reabra el
# fichero y comprime las copias antiguas, conservando LOG_KEEP.
#
# Uso: log-rotate.sh            -> bucle (lo lanza supervisord), revisa cada hora
#      log-rotate.sh --once     -> una sola pasada (pruebas)

LOG_DIR="${LOG_DIR:-/var/www/html/storage/logs}"
LOG_MAX_MB="${LOG_MAX_MB:-20}"
LOG_KEEP="${LOG_KEEP:-5}"
LOGS="nginx-access.log nginx-error.log"

rotate() {
    f="$LOG_DIR/$1"
    [ -f "$f" ] || return 0

    size_mb=$(( $(wc -c < "$f") / 1048576 ))
    [ "$size_mb" -ge "$LOG_MAX_MB" ] || return 0

    # Desplazar copias: .4.gz -> .5.gz, ..., .1.gz -> .2.gz
    i=$LOG_KEEP
    rm -f "$f.$i.gz"
    while [ "$i" -gt 1 ]; do
        prev=$((i - 1))
        [ -f "$f.$prev.gz" ] && mv "$f.$prev.gz" "$f.$i.gz"
        i=$prev
    done

    mv "$f" "$f.1"
    nginx -s reopen          # nginx crea de nuevo el fichero y lo abre
    gzip -f "$f.1"
    echo "$(date '+%F %T') rotado $1 (${size_mb} MB)"
}

run_once() {
    for l in $LOGS; do rotate "$l"; done
}

if [ "$1" = "--once" ]; then
    run_once
    exit 0
fi

sleep 60   # dejar que nginx arranque
while true; do
    run_once
    sleep 3600
done
