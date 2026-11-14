<?php
/** Set the native WordPress Site Icon once, preserving administrator replacements. */
if ( get_option( 'site_icon' ) || get_option( 'rivage_site_icon_seeded' ) ) { WP_CLI::success( 'Keeping the existing Site Icon choice.' ); return; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
foreach ( array( 32, 180, 192, 270 ) as $size ) { add_image_size( 'rivage-site-icon-' . $size, $size, $size, true ); }
$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'meta_key' => '_rivage_site_icon', 'meta_value' => '1' ) );
if ( $existing ) { $id = $existing[0]->ID; }
else {
    $tmp = wp_tempnam( 'site-icon.png' );
    if ( ! $tmp || ! copy( get_stylesheet_directory() . '/assets/images/site-icon.png', $tmp ) ) { WP_CLI::error( 'Could not prepare the Site Icon.' ); }
    $id = media_handle_sideload( array( 'name' => 'site-icon.png', 'tmp_name' => $tmp ), 0, 'Maison Rivage — Site Icon' );
    if ( is_wp_error( $id ) ) { if ( file_exists( $tmp ) ) { unlink( $tmp ); } WP_CLI::error( $id->get_error_message() ); }
    update_post_meta( $id, '_rivage_site_icon', 1 );
    update_post_meta( $id, '_wp_attachment_image_alt', 'Maison Rivage monogram with ocean waves' );
}
update_option( 'site_icon', $id );
update_option( 'rivage_site_icon_seeded', 1, false );
WP_CLI::success( 'Maison Rivage native Site Icon configured.' );
