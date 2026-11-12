<?php
/**
 * Plugin Name: Maison Rivage Core
 * Description: Hotel business functionality for the Maison Rivage portfolio project.
 * Version: 0.2.0
 * Requires at least: 6.7
 * Requires PHP: 8.3
 * Text Domain: maison-rivage-core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/display.php';

add_action( 'init', 'MaisonRivage\\register_catalogue' );
