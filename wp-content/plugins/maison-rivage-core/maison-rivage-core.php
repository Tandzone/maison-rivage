<?php
/**
 * Plugin Name: Maison Rivage Core
 * Description: Hotel business functionality for the Maison Rivage portfolio project.
 * Version: 0.6.0
 * Requires at least: 6.7
 * Requires PHP: 8.3
 * Text Domain: maison-rivage-core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/display.php';
require_once __DIR__ . '/includes/booking-store.php';
require_once __DIR__ . '/includes/booking-mail.php';
require_once __DIR__ . '/includes/booking-public.php';
require_once __DIR__ . '/includes/booking-admin.php';

add_action( 'init', 'MaisonRivage\\booking_schema', 5 );
add_action( 'init', 'MaisonRivage\\register_catalogue' );

require_once __DIR__ . '/includes/events-store.php';
require_once __DIR__ . '/includes/events-admin.php';
require_once __DIR__ . '/includes/events-public.php';
add_action( 'init', 'MaisonRivage\\event_schema', 6 );
add_action( 'init', 'MaisonRivage\\register_events' );

require_once __DIR__ . '/includes/contact.php';

require_once __DIR__ . '/includes/gallery.php';
