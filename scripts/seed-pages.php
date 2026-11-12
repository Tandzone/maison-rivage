<?php
/** Create starter pages once; preserve editorial changes on subsequent runs. */
$pages = array(
    'home'           => 'Home',
    'about'          => 'About',
    'contact'        => 'Contact & Location',
    'booking'        => 'Book Your Stay',
    'journal'        => 'Journal',
    'booking-policy' => 'Booking Policy',
    'privacy-policy' => 'Privacy Policy',
);

foreach ( $pages as $slug => $title ) {
    if ( get_page_by_path( $slug ) ) {
        continue;
    }
    $id = wp_insert_post(
        array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_name'    => $slug,
            'post_title'   => $title,
            'post_content' => '<!-- wp:paragraph --><p>Maison Rivage is a fictional hotel website under development for a WordPress portfolio.</p><!-- /wp:paragraph -->',
        ),
        true
    );
    if ( is_wp_error( $id ) ) {
        WP_CLI::error( $id->get_error_message() );
    }
}

// Configure front/posts pages once, without overwriting later editor choices.
if ( ! get_option( 'rivage_starter_pages_configured' ) ) {
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', get_page_by_path( 'home' )->ID );
    update_option( 'page_for_posts', get_page_by_path( 'journal' )->ID );
    update_option( 'rivage_starter_pages_configured', 1 );
}
WP_CLI::success( 'Starter pages ready; existing page content preserved.' );
