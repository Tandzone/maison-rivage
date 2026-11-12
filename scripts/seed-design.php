<?php
/** Seed design pages and migrate untouched V1 previews; preserve editor changes. */
$layouts = array(
    'home' => array( 'Home', 'home' ),
    'rooms' => array( 'Rooms & Suites', 'rooms' ),
    'events' => array( 'Experiences & Events', 'events' ),
    'about' => array( 'About', 'about' ),
    'booking' => array( 'Book Your Stay', 'booking' ),
    'contact' => array( 'Contact & Location', 'contact' ),
    'booking-policy' => array( 'Booking Policy', 'booking-policy' ),
    'privacy-policy' => array( 'Privacy Policy', 'privacy' ),
    'rooms/garden-room' => array( 'Garden Room', 'garden-room' ),
    'rooms/ocean-balcony-room' => array( 'Ocean Balcony Room', 'ocean-balcony-room' ),
    'rooms/rivage-suite' => array( 'Rivage Suite', 'rivage-suite' ),
);
$starter = '<!-- wp:paragraph --><p>Maison Rivage is a fictional hotel website under development for a WordPress portfolio.</p><!-- /wp:paragraph -->';
$previous_hashes = json_decode( file_get_contents( __DIR__ . '/design-v1-hashes.json' ), true );

foreach ( $layouts as $path => $layout ) {
    $page = get_page_by_path( $path );
    $seed_version = $page ? (int) get_post_meta( $page->ID, '_rivage_design_seeded', true ) : 0;
    $migrate = false;
    if ( $seed_version ) {
        if ( 1 !== $seed_version || ! isset( $previous_hashes[ $path ] ) ) {
            continue;
        }
        $current_hash = hash( 'sha256', str_replace( home_url(), '{site-url}', $page->post_content ) );
        if ( ! hash_equals( $previous_hashes[ $path ], $current_hash ) ) {
            WP_CLI::warning( 'Preserving edited design page: ' . $path );
            continue;
        }
        $migrate = true;
    }
    // Only replace the original untouched WordPress privacy draft.
    $default_privacy = $page && 'privacy-policy' === $path && 'draft' === $page->post_status
        && (int) get_option( 'wp_page_for_privacy_policy' ) === $page->ID
        && $page->post_modified_gmt === $page->post_date_gmt;
    if ( $page && ! $migrate && $page->post_content !== $starter && ! $default_privacy ) {
        WP_CLI::warning( 'Preserving edited page: ' . $path );
        continue;
    }
    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'maison-rivage/' . $layout[1] );
    if ( ! $pattern ) {
        WP_CLI::error( 'Missing design pattern: ' . $layout[1] );
    }
    if ( $migrate ) {
        // Keep the previous preview as a recoverable snapshot, independent of revisions settings.
        update_post_meta( $page->ID, '_rivage_design_v1_content', $page->post_content );
    }
    $parent = str_contains( $path, '/' ) ? get_page_by_path( 'rooms' )->ID : 0;
    $id = wp_insert_post(
        array(
            'ID' => $page ? $page->ID : 0,
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $layout[0],
            'post_name' => basename( $path ),
            'post_parent' => $parent,
            'post_content' => $pattern['content'],
        ),
        true
    );
    if ( is_wp_error( $id ) ) {
        WP_CLI::error( $id->get_error_message() );
    }
    update_post_meta( $id, '_rivage_design_seeded', 2 );
}
$sample = get_page_by_path( 'hello-world', OBJECT, 'post' );
if ( $sample && 'Hello world!' === $sample->post_title && str_contains( $sample->post_content, 'Welcome to WordPress.' ) ) {
    wp_trash_post( $sample->ID );
}
flush_rewrite_rules( false );
WP_CLI::success( 'Design pages ready. Existing seeded/editor content preserved.' );
