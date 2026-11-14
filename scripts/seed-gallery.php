<?php
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$hashes = json_decode( file_get_contents( __DIR__ . '/gallery-preview-hashes.json' ), true );
$categories = array(
    'garden-room' => array( 'Garden Room', array( 'Bedroom opening onto the garden terrace', 'Shaded garden terrace', 'Ensuite with walk-in shower' ), array( 'Garden bedroom with white linen, green textiles and timber doors opening onto tropical greenery', 'Woven chair and timber table on a shaded ground-level garden terrace', 'Garden Room bathroom with a timber vanity, stone basin and glass walk-in shower' ) ),
    'ocean-balcony-room' => array( 'Ocean Balcony Room', array( 'Bedroom with ocean balcony', 'Balcony above the cove', 'Ensuite with walk-in shower' ), array( 'Bedroom opening onto a timber balcony above the sandy cove', 'Two woven chairs on an ocean-view balcony with timber railing', 'Ocean Balcony Room bathroom with a stone basin, timber vanity and glass walk-in shower' ) ),
    'rivage-suite' => array( 'Rivage Suite', array( 'Bedroom and private lounge', 'Terrace overlooking the cove', 'Spacious ensuite with double vanity' ), array( 'Spacious suite with a double bed, woven sofa and lounge chair facing an ocean terrace', 'Woven seating on the suite terrace overlooking the cove and green headland', 'Rivage Suite bathroom with two stone basins, a timber vanity and walk-in shower' ) ),
);
foreach ( $categories as $slug => $data ) {
    $page = get_page_by_path( 'rooms/' . $slug );
    if ( ! $page ) { WP_CLI::error( 'Missing room category: ' . $slug ); }
    $ids = array();
    foreach ( array( 'bedroom', 'terrace', 'bathroom' ) as $i => $kind ) {
        $file = $slug . '-' . $kind . '.webp';
        $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => array( 'inherit', 'trash' ), 'numberposts' => 1, 'meta_key' => '_rivage_gallery_seed', 'meta_value' => $file ) );
        if ( $existing ) { $ids[] = $existing[0]->ID; continue; }
        $tmp = wp_tempnam( $file );
        if ( ! $tmp || ! copy( get_stylesheet_directory() . '/assets/images/' . $file, $tmp ) ) { WP_CLI::error( 'Could not prepare image: ' . $file ); }
        $id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), $page->ID, $data[0] . ' — ' . $data[1][$i] );
        if ( is_wp_error( $id ) ) { if ( file_exists( $tmp ) ) { unlink( $tmp ); } WP_CLI::error( $id->get_error_message() ); }
        update_post_meta( $id, '_rivage_gallery_seed', $file );
        update_post_meta( $id, '_wp_attachment_image_alt', $data[2][$i] );
        wp_update_post( array( 'ID' => $id, 'post_excerpt' => $data[1][$i] . ' · ' . $data[0] ) );
        $ids[] = $id;
    }
    if ( ! metadata_exists( 'post', $page->ID, '_rivage_gallery' ) ) { update_post_meta( $page->ID, '_rivage_gallery', $ids ); }
    if ( ! get_post_meta( $page->ID, '_rivage_gallery_feature_seeded', true ) ) {
        $old = get_post_thumbnail_id( $page->ID );
        if ( ! $old || 'coastal-suite.png' === get_post_meta( $old, '_rivage_seed_asset', true ) ) { set_post_thumbnail( $page->ID, $ids[0] ); }
        update_post_meta( $page->ID, '_rivage_gallery_feature_seeded', 1 );
    }
    if ( get_post_meta( $page->ID, '_rivage_gallery_page', true ) ) { continue; }
    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'maison-rivage/' . $slug );
    if ( hash_equals( $hashes[$slug], hash( 'sha256', str_replace( home_url(), '{site-url}', $page->post_content ) ) ) ) {
        update_post_meta( $page->ID, '_rivage_pre_gallery_content', $page->post_content );
        wp_update_post( array( 'ID' => $page->ID, 'post_content' => $pattern['content'] ) );
        update_post_meta( $page->ID, '_rivage_gallery_page', 1 );
    } elseif ( $page->post_content === $pattern['content'] ) { update_post_meta( $page->ID, '_rivage_gallery_page', 1 ); }
    else { WP_CLI::warning( 'Preserving edited room page; insert [rivage_room_gallery] manually: ' . $slug ); }
}
WP_CLI::success( 'Room galleries and distinct catalogue photographs ready; edited content preserved.' );
