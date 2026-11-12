<?php
/** Reproducible demo content. Existing edited records are never reset. */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once __DIR__ . '/connect-content.php';

function rivage_seed_image( $file, $title, $alt ) {
    $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1,
        'meta_key' => '_rivage_seed_asset', 'meta_value' => $file ) );
    if ( $existing ) { return $existing[0]->ID; }
    $source = get_stylesheet_directory() . '/assets/images/' . $file;
    if ( ! is_readable( $source ) ) { WP_CLI::error( 'Missing demo image: ' . $file ); }
    $tmp = wp_tempnam( $file );
    if ( ! $tmp || ! copy( $source, $tmp ) ) { WP_CLI::error( 'Could not prepare demo image.' ); }
    $id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), 0, $title );
    if ( is_wp_error( $id ) ) {
        if ( file_exists( $tmp ) ) { unlink( $tmp ); }
        WP_CLI::error( $id->get_error_message() );
    }
    update_post_meta( $id, '_rivage_seed_asset', $file );
    update_post_meta( $id, '_wp_attachment_image_alt', $alt );
    return $id;
}

$images = array(
    'suite' => rivage_seed_image( 'coastal-suite.png', 'Maison Rivage — Room and balcony concept', 'Maison Rivage coastal room and balcony concept' ),
    'hall' => rivage_seed_image( 'hall.png', 'Maison Rivage — Hall concept', 'Timber reception and lounge opening onto a tropical garden' ),
    'cello' => rivage_seed_image( 'cello-evening.png', 'Maison Rivage — Recital concept', 'A cellist performs for a small audience in the hall' ),
);
$types = array(
    'garden-room' => array( 2, 2, 2, 'Garden terrace', 9500, 'A quiet corner among the leaves, with a terrace for slow mornings.' ),
    'ocean-balcony-room' => array( 2, 2, 2, 'Ocean-view balcony', 13500, 'Open the doors to salt air and a sheltered stretch of blue.' ),
    'rivage-suite' => array( 3, 2, 1, 'Ocean-view terrace', 19500, 'A little more room to settle in, with a lounge and the ocean beyond.' ),
);
$keys = array( 'capacity', 'max_adults', 'max_children', 'outdoor', 'rate_cents' );
foreach ( $types as $slug => $values ) {
    $page = get_page_by_path( 'rooms/' . $slug );
    if ( ! $page ) { WP_CLI::error( 'Missing category page: ' . $slug ); }
    if ( ! get_post_meta( $page->ID, '_rivage_content_seeded', true ) ) {
        foreach ( $keys as $index => $key ) {
            if ( ! metadata_exists( 'post', $page->ID, '_rivage_' . $key ) ) { update_post_meta( $page->ID, '_rivage_' . $key, $values[ $index ] ); }
        }
        update_post_meta( $page->ID, '_rivage_room_type', 1 );
        update_post_meta( $page->ID, '_rivage_private_events', 'rivage-suite' === $slug );
        if ( ! $page->post_excerpt ) { wp_update_post( array( 'ID' => $page->ID, 'post_excerpt' => $values[5] ) ); }
        if ( ! has_post_thumbnail( $page->ID ) ) { set_post_thumbnail( $page->ID, $images['suite'] ); }
        update_post_meta( $page->ID, '_rivage_content_seeded', 1 );
    }
    $prefix = array( 'garden-room' => 'Garden', 'ocean-balcony-room' => 'Ocean', 'rivage-suite' => 'Suite' )[ $slug ];
    for ( $number = 1; $number <= 2; $number++ ) {
        $seed_key = $slug . '-' . $number;
        $existing = get_posts( array( 'post_type' => 'rivage_room', 'post_status' => array( 'publish', 'draft', 'private', 'trash' ),
            'numberposts' => 1, 'meta_key' => '_rivage_seed_key', 'meta_value' => $seed_key ) );
        if ( $existing ) { continue; }
        $id = wp_insert_post( array( 'post_type' => 'rivage_room', 'post_status' => 'publish',
            'post_title' => $prefix . ' ' . str_pad( $number, 2, '0', STR_PAD_LEFT ),
            'meta_input' => array( '_rivage_type_id' => $page->ID, '_rivage_status' => 'active', '_rivage_seed_key' => $seed_key ) ), true );
        if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
    }
}

// Migrate only untouched design pages; editor changes are explicitly preserved.
$hashes = json_decode( file_get_contents( __DIR__ . '/design-v2-hashes.json' ), true );
foreach ( $hashes as $path => $expected ) {
    $page = get_page_by_path( $path );
    if ( ! $page || get_post_meta( $page->ID, '_rivage_dynamic_content', true ) ) { continue; }
    $current = hash( 'sha256', str_replace( home_url(), '{site-url}', $page->post_content ) );
    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'maison-rivage/' . basename( $path ) );
    if ( hash_equals( $expected, $current ) ) {
        update_post_meta( $page->ID, '_rivage_design_v2_content', $page->post_content );
        $kind = in_array( $path, array( 'home', 'rooms' ), true ) ? $path : 'type';
        wp_update_post( array( 'ID' => $page->ID, 'post_content' => serialize_blocks( rivage_content_blocks( parse_blocks( $page->post_content ), $kind ) ) ) );
        update_post_meta( $page->ID, '_rivage_dynamic_content', 1 );
    } elseif ( $pattern && $page->post_content === $pattern['content'] ) {
        // Fresh setup already used the new dynamic patterns.
        update_post_meta( $page->ID, '_rivage_dynamic_content', 1 );
    } else { WP_CLI::warning( 'Preserving edited page; insert dynamic shortcodes manually if desired: ' . $path ); }
}

$articles = array(
    'a-house-open-to-the-ocean' => array( 'A house open to the ocean', 'Rooms & Design', $images['suite'],
        'Timber, woven textures and open doors shape the atmosphere imagined for Maison Rivage.',
        array( 'At Maison Rivage, the room and the landscape belong to the same story. Pale stone, warm timber and soft green textiles echo the garden and the shore.', 'The six-room collection offers three ways to stay: a garden terrace, an ocean balcony or a suite with a lounge and a private terrace.', 'Maison Rivage is a fictional hotel concept. These generated images explore its architectural identity.' ) ),
    'a-place-to-gather' => array( 'A place to gather, a reason to linger', 'Life at the House', $images['hall'],
        'The hall is imagined as a shared living space, from quiet reading to small cultural evenings.',
        array( 'A reception can be more than a place to collect a key. At Maison Rivage, it opens into a lounge with woven chairs, a small exhibition alcove and a view through the garden.', 'The same space can make room for an intimate gathering. Furniture can move; the quiet character of the house stays.', 'This article presents an imagined setting. No event dates are announced here.' ) ),
    'music-in-the-hall' => array( 'An evening of strings and sea air', 'Life at the House', $images['cello'],
        'A cello recital offers a glimpse of the small gatherings imagined for the house.',
        array( 'A few chairs, a cello and an attentive audience: the recital concept brings a different rhythm to the hall.', 'The room remains intimate, with the garden and ocean nearby. This is the scale of gathering we imagine for Maison Rivage.', 'This is an illustrative portfolio story, not an announcement of a scheduled performance.' ) ),
);
foreach ( $articles as $slug => $article ) {
    $existing = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'private', 'trash' ),
        'numberposts' => 1, 'meta_key' => '_rivage_seed_article', 'meta_value' => $slug ) );
    if ( $existing || get_page_by_path( $slug, OBJECT, 'post' ) ) { continue; }
    $term = term_exists( $article[1], 'category' );
    if ( ! $term ) { $term = wp_insert_term( $article[1], 'category' ); }
    if ( is_wp_error( $term ) ) { WP_CLI::error( $term->get_error_message() ); }
    $content = '';
    foreach ( $article[4] as $paragraph ) { $content .= '<!-- wp:paragraph --><p>' . esc_html( $paragraph ) . '</p><!-- /wp:paragraph -->'; }
    $id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug,
        'post_title' => $article[0], 'post_excerpt' => $article[3], 'post_content' => $content,
        'post_category' => array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ),
        'meta_input' => array( '_rivage_seed_article' => $slug ) ), true );
    if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
    set_post_thumbnail( $id, $article[2] );
}
if ( '/%postname%/' === get_option( 'permalink_structure' ) ) {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure( '/journal/%postname%/' );
}
flush_rewrite_rules( false );
WP_CLI::success( 'Hotel catalogue ready: categories, private room inventory, media and journal content. Existing records preserved.' );
