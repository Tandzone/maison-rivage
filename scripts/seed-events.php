<?php
/** Seed once; preserve dates and all subsequent editorial/operational edits. */
use function MaisonRivage\event_change;
use function MaisonRivage\active_rooms;

wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
$hashes = json_decode( file_get_contents( __DIR__ . '/event-preview-hashes.json' ), true );
foreach ( $hashes as $path => $hash ) {
    $page = get_page_by_path( $path );
    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'maison-rivage/' . $path );
    if ( ! $page || ! $pattern || get_post_meta( $page->ID, '_rivage_events_page', true ) ) { continue; }
    if ( hash_equals( $hash, hash( 'sha256', str_replace( home_url(), '{site-url}', $page->post_content ) ) ) ) {
        update_post_meta( $page->ID, '_rivage_pre_events_content', $page->post_content );
        wp_update_post( array( 'ID' => $page->ID, 'post_content' => $pattern['content'] ) );
        update_post_meta( $page->ID, '_rivage_events_page', 1 );
    } elseif ( $page->post_content === $pattern['content'] ) { update_post_meta( $page->ID, '_rivage_events_page', 1 ); }
    else { WP_CLI::warning( 'Preserving edited page; insert [rivage_events] manually if desired: ' . $path ); }
}
$image = static function ( $file ) {
    $ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_rivage_seed_asset', 'meta_value' => $file ) );
    return $ids ? $ids[0] : 0;
};
$today = new DateTimeImmutable( 'today', new DateTimeZone( 'Indian/Antananarivo' ) );
$suite = get_page_by_path( 'rooms/rivage-suite' );
$rooms = $suite ? active_rooms( $suite->ID ) : array();
$events = array(
    'strings-and-sea-air' => array( 'Strings & sea air', 5, '18:00', '19:30', 'hall', 'cello-evening.png', 'An intimate cello evening in the open hall.', 'A small audience, a cello and the garden just beyond. An unhurried evening imagined for the house, with time to linger afterwards.' ),
    'craft-and-coastal-light' => array( 'Craft & coastal light', 12, '15:00', '17:00', 'suite', 'coastal-suite.png', 'A quiet conversation about texture, materials and coastal living.', 'The suite lounge becomes a setting for a small design conversation. Timber, woven fibres and the ocean light offer a starting point for an imagined afternoon together.' ),
    'stories-in-the-hall' => array( 'Stories in the hall', 19, '17:00', '18:00', 'hall', 'hall.png', 'An early-evening reading in the shared living space.', 'Pull up a chair for a reading and a conversation about journeys, gardens and the sea. This fictional gathering explores the hall as a shared living room.' ),
    'an-afternoon-at-the-house' => array( 'An afternoon at the house', -7, '15:00', '16:30', 'hall', 'hall.png', 'A look back at an imagined afternoon of conversation.', 'The first gathering in our fictional calendar brought a few chairs together in the hall. This archive entry demonstrates how past events remain available to browse.' ),
);
foreach ( $events as $slug => $event ) {
    $existing = get_posts( array( 'post_type' => 'rivage_event', 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future', 'trash' ), 'numberposts' => 1, 'meta_key' => '_rivage_seed_event', 'meta_value' => $slug ) );
    if ( $existing || get_page_by_path( $slug, OBJECT, 'rivage_event' ) ) { continue; }
    $id = wp_insert_post( array( 'post_type' => 'rivage_event', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $event[0], 'post_excerpt' => $event[6],
        'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $event[7] ) . '</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>This is fictional demonstration content. No real attendance is offered.</p><!-- /wp:paragraph -->', 'meta_input' => array( '_rivage_seed_event' => $slug ) ), true );
    if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
    set_post_thumbnail( $id, $image( $event[5] ) );
    $date = $today->modify( sprintf( '%+d days', $event[1] ) )->format( 'Y-m-d' );
    $result = event_change( $id, 'schedule', array( 'start' => $date . 'T' . $event[2], 'end' => $date . 'T' . $event[3], 'venue' => $event[4], 'room_id' => $rooms ? $rooms[0]->ID : 0 ), 0 );
    if ( is_wp_error( $result ) ) { WP_CLI::warning( 'Event story created but not scheduled: ' . $slug . '. ' . $result->get_error_message() ); }
}
foreach ( array(
    'textures-of-a-coastal-house' => array( 'Textures of a coastal house', 'Rooms & Design', 'coastal-suite.png', 'Pale stone, woven fibres and the warmth of timber.', 'The material palette of our imagined hotel begins with simple textures. Natural fibres, pale stone and timber connect the rooms to the garden and the shoreline.' ),
    'a-slow-morning-by-the-garden' => array( 'A slow morning by the garden', 'Life at the House', 'hall.png', 'A quiet start, with the doors open to the garden.', 'A slow morning is part of the atmosphere imagined for Maison Rivage. The shared hall offers a shaded place to read, talk and watch the light change.' ),
) as $slug => $article ) {
    $exists = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future', 'trash' ), 'numberposts' => 1, 'meta_key' => '_rivage_seed_article', 'meta_value' => $slug ) );
    if ( $exists || get_page_by_path( $slug, OBJECT, 'post' ) ) { continue; }
    $term = term_exists( $article[1], 'category' );
    if ( ! $term ) { $term = wp_insert_term( $article[1], 'category' ); }
    if ( is_wp_error( $term ) ) { WP_CLI::error( $term->get_error_message() ); }
    $id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $article[0], 'post_excerpt' => $article[3], 'post_category' => array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ),
        'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $article[4] ) . '</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>A fictional portfolio story, illustrated with AI-generated hotel concepts.</p><!-- /wp:paragraph -->', 'meta_input' => array( '_rivage_seed_article' => $slug ) ), true );
    if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
    set_post_thumbnail( $id, $image( $article[2] ) );
}
flush_rewrite_rules( false );
WP_CLI::success( 'Event stories, checked schedules and journal content ready. Existing records preserved.' );
