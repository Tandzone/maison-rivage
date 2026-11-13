<?php
/** Local preview only. Loaded by wp-config before WordPress builds asset URLs. */
$rivage_preview = rtrim( (string) getenv( 'RIVAGE_PREVIEW_URL' ), '/' );
$rivage_parts = parse_url( $rivage_preview );
$rivage_host = strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) );
if ( is_array( $rivage_parts ) && 'https' === ( $rivage_parts['scheme'] ?? '' )
    && ! isset( $rivage_parts['user'] ) && ! isset( $rivage_parts['pass'] )
    && ! isset( $rivage_parts['port'] ) && empty( $rivage_parts['path'] )
    && empty( $rivage_parts['query'] ) && empty( $rivage_parts['fragment'] )
    && preg_match( '/^[a-z0-9.-]+\.ts\.net$/D', $rivage_parts['host'] ?? '' )
    && in_array( $rivage_host, array( $rivage_parts['host'], $rivage_parts['host'] . ':443' ), true ) ) {
    define( 'WP_HOME', $rivage_preview );
    define( 'WP_SITEURL', $rivage_preview );
    // This exact, configured hostname is served by the local Tailscale HTTPS proxy.
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
}
unset( $rivage_preview, $rivage_parts, $rivage_host );
