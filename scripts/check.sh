#!/bin/sh
set -eu
wp core is-installed
wp core verify-checksums --exclude=wp-config-docker.php
wp theme is-active maison-rivage
wp plugin is-active maison-rivage-core
find /var/www/html/wp-content/plugins/maison-rivage-core /var/www/html/wp-content/mu-plugins /workspace/scripts -name '*.php' | while IFS= read -r file; do php -l "$file"; done
wp eval 'if ( wp_get_environment_type() !== "local" ) { WP_CLI::error("Expected local environment."); } if ( ! wp_mail("guest@example.test", "Maison Rivage local smoke test", "Local mail capture is working.") ) { WP_CLI::error("Mail delivery failed."); } WP_CLI::success("Local environment and SMTP send verified. Check Mailpit for the message.");'
