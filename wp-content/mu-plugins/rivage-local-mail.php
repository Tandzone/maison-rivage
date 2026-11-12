<?php
/**
 * Plugin Name: Maison Rivage — Local Mail
 * Description: Routes local development email to Mailpit; inactive outside local environments.
 */

defined( 'ABSPATH' ) || exit;

// WordPress validates the sender before phpmailer_init; localhost is not a valid mail domain.
add_filter(
    'wp_mail_from',
    static function ( $from ) {
        return 'local' === wp_get_environment_type() && getenv( 'RIVAGE_SMTP_HOST' )
            ? 'hello@maison-rivage.test' : $from;
    }
);

add_filter(
    'wp_mail_from_name',
    static function ( $name ) {
        return 'local' === wp_get_environment_type() && getenv( 'RIVAGE_SMTP_HOST' )
            ? 'Maison Rivage' : $name;
    }
);

add_action(
    'phpmailer_init',
    static function ( $mailer ) {
        if ( 'local' !== wp_get_environment_type() || ! getenv( 'RIVAGE_SMTP_HOST' ) ) {
            return;
        }
        $mailer->isSMTP();
        $mailer->Host        = getenv( 'RIVAGE_SMTP_HOST' );
        $mailer->Port        = (int) ( getenv( 'RIVAGE_SMTP_PORT' ) ?: 1025 );
        $mailer->SMTPAuth    = false;
        $mailer->SMTPAutoTLS = false;
        $mailer->SMTPSecure  = '';
    }
);
