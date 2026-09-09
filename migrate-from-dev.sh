#!/bin/bash
# Migrate data from local dev docker (pb_wp + pb_db) into a running prod stack.
#
# Run this on your DEV machine (Windows / Mac / Linux), NOT on the Oracle VM.
#
# Prereqs:
#   - The prod stack is up and reachable (ssh-tunnel port 80 or via tunnel URL)
#   - You have wp-cli available locally OR use docker exec into the prod wp container
#
# Usage:
#   ./migrate-from-dev.sh prod-user@prod-host

set -euo pipefail

PROD_SSH="${1:-opc@CHANGE_ME_ORACLE_IP}"
WP_CONTAINER="${WP_CONTAINER:-piano-booking-deploy-wordpress-1}"
DB_CONTAINER_LOCAL="${DB_CONTAINER_LOCAL:-pb_db}"

echo "=== 1. Export DB from local docker ==="
docker exec "$DB_CONTAINER_LOCAL" sh -c \
  'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" wordpress' > wp_dump.sql
echo "✅ Exported $(wc -l < wp_dump.sql) lines to wp_dump.sql"

echo "=== 2. Copy WP uploads/plugins/themes (excluding piano-booking + piano-theme, those are baked in) ==="
docker cp pb_wp:/var/www/html/wp-content ./wp_content_export
# Remove the things that are baked into the image
rm -rf ./wp_content_export/plugins/piano-booking ./wp_content_export/themes/piano-theme
echo "✅ Exported wp-content (excluding baked-in plugin + theme)"

echo "=== 3. Upload to prod VM ==="
rsync -avz -e ssh \
  ./wp_dump.sql ./wp_content_export/ \
  "$PROD_SSH":~/pb-import/
echo "✅ Uploaded to $PROD_SSH:~/pb-import/"

echo "=== 4. Import on prod ==="
ssh "$PROD_SSH" <<'REMOTE'
  set -euo pipefail
  cd ~/pb-import

  echo "Stopping WP container while we import"
  cd ~/piano-booking-deploy
  docker compose stop wordpress

  echo "Importing DB"
  docker compose exec -T db sh -c \
    "mysql -u root -p\"\$MARIADB_ROOT_PASSWORD\" pianobooking" < ~/pb-import/wp_dump.sql

  echo "Copying uploaded content into WP volume"
  docker compose cp wordpress /var/www/html/wp-content /tmp/wp-content.bak || true
  docker compose cp wordpress /var/www/html/wp-content ./wp-content.before
  # Restore our baked-in plugin + theme (overwritten by import)
  docker compose cp wordpress wp-content/plugins/piano-booking /var/www/html/wp-content/plugins/
  docker compose cp wordpress wp-content/themes/piano-theme /var/www/html/wp-content/themes/

  echo "Merging uploaded content into volume"
  docker compose exec -T wordpress sh -c 'rm -rf /var/www/html/wp-content/uploads'
  docker cp ~/pb-import/wp_content_export/uploads/. \
    $(docker compose ps -q wordpress):/var/www/html/wp-content/uploads/
  docker compose exec -T wordpress chown -R www-data:www-data /var/www/html/wp-content

  echo "Restarting WP"
  docker compose start wordpress
REMOTE

echo ""
echo "=== ✅ Migration complete ==="
echo "Now run search-replace to update URLs:"
echo "  ssh $PROD_SSH 'cd ~/piano-booking-deploy && docker compose exec wordpress wp search-replace \"http://localhost:8080\" \"\$WP_HOME\" --allow-root'"