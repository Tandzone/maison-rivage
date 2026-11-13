#!/bin/sh
set -eu

if ! wp core is-installed; then
    wp core install --url="$WP_URL" --title='Maison Rivage' \
        --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
        --admin_email="$WP_ADMIN_EMAIL" --skip-email
    wp option update timezone_string Indian/Antananarivo
    wp option update blogdescription 'A fictional boutique retreat in Madagascar'
    wp option update blog_public 0
    wp rewrite structure '/%postname%/'
fi

if ! wp theme is-installed twentytwentyfive; then
    wp theme install twentytwentyfive --version=1.5
fi
wp theme activate maison-rivage
wp plugin activate maison-rivage-core
wp eval-file /workspace/scripts/seed-pages.php
wp eval-file /workspace/scripts/seed-design.php
wp eval-file /workspace/scripts/seed-hotel.php
wp eval-file /workspace/scripts/seed-booking.php
wp eval-file /workspace/scripts/seed-events.php
wp eval-file /workspace/scripts/seed-polish.php
echo 'Setup complete. Admin credentials are in your local .env file.'
