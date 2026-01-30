#!/bin/bash
set -euo pipefail

a2enmod headers
echo "🔧 Syncing file on EFS..."
rsync -av --progress /tmp/sites/ /var/www/html/web/sites/

echo "🔧 Starting symlink creation..."
cd /var/www/html/web

# Define site list directly here
sites=("wmr-usavan" "wmr-usapc" "msmr" "msmr-malta" "msmr-polen")

for site in "${sites[@]}"; do
  if [ ! -L "$site" ]; then
    ln -s . "$site"
    echo "✅ Created symlink: $site -> ."
  else
    echo "ℹ️ Symlink already exists: $site"
  fi
done

echo "✅ All symlinks created successfully!"
echo "Cleaning cache"
cd /var/www/html
./vendor/bin/drush cr

echo "Changing subsites permissions"
chown -R www-data:www-data /var/www/html/web/sites

echo "Starting Apache"
# Start Apache
exec "$@"
echo "Application Started"