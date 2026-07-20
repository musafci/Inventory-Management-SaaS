#!/usr/bin/env bash
set -euo pipefail

# Point Apache at the Laravel public directory so /login, /dashboard, etc. work on port 80.
# Run: sudo ./deploy/enable-apache.sh

CONF_SRC="$(cd "$(dirname "$0")" && pwd)/apache-inventory.conf"
CONF_DST="/etc/apache2/sites-available/inventory.conf"

cp "$CONF_SRC" "$CONF_DST"
a2enmod rewrite
a2ensite inventory.conf
a2dissite 000-default.conf
apache2ctl configtest
systemctl reload apache2

chown -R www-data:www-data /var/www/Inventory-Management-SaaS/storage /var/www/Inventory-Management-SaaS/bootstrap/cache

echo "Apache is now serving Laravel from /var/www/Inventory-Management-SaaS/public"
echo "Ensure APP_URL=http://localhost in .env, then run: php artisan config:clear"
