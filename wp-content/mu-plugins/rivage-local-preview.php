<?php
/**
 * Plugin Name: Maison Rivage — Local Preview
 * Description: Normalize saved local/preview content URLs without changing the database.
 */
defined( 'ABSPATH' ) || exit;
add_filter( 'the_content', static function ( $content ) {
    if ( 'local' !== wp_get_environment_type() ) { return $content; }
    $local = rtrim( (string) getenv( 'RIVAGE_LOCAL_URL' ), '/' );
    $preview = rtrim( (string) getenv( 'RIVAGE_PREVIEW_URL' ), '/' );
    if ( ! $local || ! $preview ) { return $content; }
    return str_replace( array( $local . '/', $preview . '/' ), trailingslashit( home_url() ), $content );
}, 0 );
