<?php
namespace MaisonRivage;

defined( 'ABSPATH' ) || exit;

function room_facts_html( $data ) {
    if ( ! $data ) { return ''; }
    $facts = array( 'Guests' => $data['guests'], 'Your outdoor space' => $data['outdoor'] );
    if ( null !== $data['rate_cents'] ) { $facts['Per room, per night'] = format_rate( $data['rate_cents'] ); }
    $html = '<dl class="rivage-room-facts">';
    foreach ( $facts as $label => $value ) {
        $html .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
    }
    return $html . '</dl>';
}

function facts_shortcode( $attrs ) {
    $attrs = shortcode_atts( array( 'type' => '' ), $attrs, 'rivage_room_facts' );
    $page = $attrs['type'] ? get_page_by_path( 'rooms/' . sanitize_title( $attrs['type'] ) ) : get_post( get_queried_object_id() );
    return room_facts_html( $page ? type_data( $page->ID ) : null );
}

function rooms_shortcode( $attrs ) {
    $attrs = shortcode_atts( array( 'layout' => 'cards' ), $attrs, 'rivage_rooms' );
    $layout = 'catalogue' === $attrs['layout'] ? 'catalogue' : 'cards';
    $rooms = array_filter( array_map( static function ( $post ) { return type_data( $post->ID ); }, room_types() ) );
    if ( ! $rooms ) { return '<p>Our room collection will be available soon.</p>'; }
    // Optional theme override; business data remains independent of presentation.
    $template = locate_template( 'hotel/room-collection.php' ) ?: dirname( __DIR__ ) . '/templates/room-collection.php';
    ob_start();
    include $template;
    return ob_get_clean();
}

add_shortcode( 'rivage_rooms', __NAMESPACE__ . '\\rooms_shortcode' );
add_shortcode( 'rivage_room_facts', __NAMESPACE__ . '\\facts_shortcode' );
