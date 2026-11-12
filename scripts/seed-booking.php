<?php
/** Upgrade untouched public pages and create verification/receipt routes. */
$hashes = json_decode( file_get_contents( __DIR__ . '/booking-preview-hashes.json' ), true );
foreach ( $hashes as $path => $hash ) {
    $page = get_page_by_path( $path );
    $slug = 'privacy-policy' === $path ? 'privacy' : $path;
    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'maison-rivage/' . $slug );
    if ( ! $page || ! $pattern || get_post_meta( $page->ID, '_rivage_booking_page', true ) ) { continue; }
    if ( hash_equals( $hash, hash( 'sha256', str_replace( home_url(), '{site-url}', $page->post_content ) ) ) ) {
        update_post_meta( $page->ID, '_rivage_pre_booking_content', $page->post_content );
        wp_update_post( array( 'ID' => $page->ID, 'post_content' => $pattern['content'] ) );
        update_post_meta( $page->ID, '_rivage_booking_page', 1 );
    } elseif ( $page->post_content === $pattern['content'] ) { update_post_meta( $page->ID, '_rivage_booking_page', 1 ); }
    else { WP_CLI::warning( 'Preserving edited page; review before enabling public booking: ' . $path ); }
}
$parent = get_page_by_path( 'booking' );
if ( ! $parent ) { WP_CLI::error( 'Create the Booking page first.' ); }
foreach ( array( 'verify' => array( 'Verify Your Email', 'rivage_verify' ), 'request-received' => array( 'Request Received', 'rivage_received' ) ) as $slug => $data ) {
    if ( get_page_by_path( 'booking/' . $slug ) ) { continue; }
    $content = '<!-- wp:group {"className":"rivage-wrap rivage-section","layout":{"type":"default"}} --><div class="wp-block-group rivage-wrap rivage-section"><!-- wp:post-title {"level":1} /--><!-- wp:shortcode -->[' . $data[1] . ']<!-- /wp:shortcode --></div><!-- /wp:group -->';
    $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_parent' => $parent->ID, 'post_name' => $slug, 'post_title' => $data[0], 'post_content' => $content ), true );
    if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
}
flush_rewrite_rules( false );
WP_CLI::success( 'Booking, verification, receipt and policy pages ready. Existing edited pages preserved.' );
