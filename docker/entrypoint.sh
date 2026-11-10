#!/bin/sh
set -e

# mod_php exige prefork: garante um único MPM carregado (evita AH00534)
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
[ -e /etc/apache2/mods-enabled/mpm_prefork.load ] || a2enmod mpm_prefork >/dev/null

# Railway/Render injetam $PORT: faz o Apache escutar nela (padrão 80)
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
  sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

# volume montado (Railway) chega como root: garante escrita para o Apache (www-data)
mkdir -p public/uploads && chown -R www-data:www-data public/uploads

# aplica o esquema (idempotente); SEED=true cria admin + dados fictícios
php bin/migrate.php
if [ "$SEED" = "true" ]; then php bin/seed.php; fi
exec "$@"
