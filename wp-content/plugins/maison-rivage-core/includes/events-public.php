<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;
function event_date_label( $row ) {
    $start = str_replace( 'T', ' ', event_local_value( $row->starts_at ) );
    $end = str_replace( 'T', ' ', event_local_value( $row->ends_at ) );
    return $start . ' – ' . ( substr( $start, 0, 10 ) === substr( $end, 0, 10 ) ? substr( $end, 11 ) : $end ) . ' · Madagascar time (UTC+3)';
}
function events_shortcode( $attrs ) {
    global $wpdb;
    $attrs = shortcode_atts( array( 'period' => 'upcoming', 'limit' => 6 ), $attrs, 'rivage_events' );
    $past = 'past' === $attrs['period'];
    $limit = min( 12, max( 1, absint( $attrs['limit'] ) ) );
    $key = $past ? 'past_page' : 'events_page';
    $page = isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ? min( 10000, max( 1, absint( $_GET[ $key ] ) ) ) : 1;
    // Teasers do not inherit catalogue pagination from the URL.
    if ( is_front_page() ) { $page = 1; }
    $comparison = $past ? '<' : '>=';
    $order = $past ? 'DESC' : 'ASC';
    $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT e.* FROM ' . booking_table( 'events' ) . " e INNER JOIN {$wpdb->posts} p ON p.ID=e.event_id WHERE p.post_type='rivage_event' AND p.post_status='publish' AND e.status='scheduled' AND e.ends_at $comparison %s ORDER BY e.starts_at $order,e.event_id $order LIMIT %d OFFSET %d", gmdate( 'Y-m-d H:i:s' ), $limit + 1, ( $page - 1 ) * $limit ) );
    if ( ! $rows ) { return '<p class="rivage-notice">' . ( $past ? 'There are no past events to show yet.' : 'New evenings are taking shape. Check back for upcoming events.' ) . '</p>'; }
    $more = count( $rows ) > $limit;
    $html = '<div class="rivage-event-grid">';
    foreach ( array_slice( $rows, 0, $limit ) as $row ) {
        $post = get_post( $row->event_id ); $url = get_permalink( $post );
        $html .= '<article class="rivage-event-card">';
        if ( has_post_thumbnail( $post ) ) { $html .= '<a tabindex="-1" aria-hidden="true" href="' . esc_url( $url ) . '">' . get_the_post_thumbnail( $post, 'large', array( 'loading' => 'lazy' ) ) . '</a>'; }
        $html .= '<div><p class="rivage-eyebrow">' . ( 'suite' === $row->venue ? 'RIVAGE SUITE' : 'IN THE HALL' ) . '</p><h3><a href="' . esc_url( $url ) . '">' . esc_html( $post->post_title ) . '</a></h3><p class="rivage-event-date">' . esc_html( event_date_label( $row ) ) . '</p><p>' . esc_html( $post->post_excerpt ) . '</p><a class="rivage-event-link" href="' . esc_url( $url ) . '">View event <span aria-hidden="true">↗</span><span class="screen-reader-text">: ' . esc_html( $post->post_title ) . '</span></a></div></article>';
    }
    $html .= '</div>';
    if ( ! is_front_page() && ( $page > 1 || $more ) ) {
        $html .= '<nav class="rivage-event-pagination" aria-label="' . ( $past ? 'Past events' : 'Upcoming events' ) . ' pages">';
        $base = get_permalink( get_queried_object_id() );
        if ( $page > 1 ) { $html .= '<a href="' . esc_url( add_query_arg( $key, $page - 1, $base ) ) . '">Previous</a> '; }
        if ( $more ) { $html .= '<a href="' . esc_url( add_query_arg( $key, $page + 1, $base ) ) . '">Next</a>'; }
        $html .= '</nav>';
    }
    return $html;
}
add_shortcode( 'rivage_events', __NAMESPACE__ . '\\events_shortcode' );
add_filter( 'the_content', static function ( $content ) {
    if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) { return $content; }
    if ( 'rivage_event' === get_post_type() ) {
        $row = event_get( get_the_ID() );
        $facts = '<aside class="rivage-event-facts" aria-label="Event details">';
        if ( $row && $row->starts_at ) {
            $label = 'scheduled' === $row->status ? ( $row->ends_at < gmdate( 'Y-m-d H:i:s' ) ? 'Past event' : 'Upcoming / current event' ) : ucfirst( $row->status );
            $facts .= '<p class="rivage-eyebrow">' . esc_html( $label ) . '</p><p><strong>' . esc_html( event_date_label( $row ) ) . '</strong></p><p>Venue: ' . ( 'suite' === $row->venue ? 'Rivage Suite · an intimate gathering' : 'Maison Rivage Hall' ) . '</p>';
        } else { $facts .= '<p>Dates to be announced.</p>'; }
        return $facts . '<p>Fictional portfolio event. No tickets, registration or real attendance are offered.</p></aside>' . $content . '<p><a href="' . esc_url( home_url( '/events/' ) ) . '">All experiences & events</a> · <a href="' . esc_url( home_url( '/rooms/' ) ) . '">Explore our rooms</a></p>';
    }
    if ( 'post' !== get_post_type() ) { return $content; }
    $related = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 3, 'post__not_in' => array( get_the_ID() ), 'category__in' => wp_get_post_categories( get_the_ID() ) ) );
    if ( ! $related ) { return $content; }
    $html = '<aside class="rivage-related-stories"><h2>More from the journal.</h2><ul>';
    foreach ( $related as $post ) { $html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $post->post_title ) . '</a></li>'; }
    return $content . $html . '</ul></aside>';
} );
add_action( 'wp_enqueue_scripts', static function () {
    wp_enqueue_style( 'rivage-events', plugins_url( 'assets/events.css', dirname( __DIR__ ) . '/maison-rivage-core.php' ), array(), (string) filemtime( dirname( __DIR__ ) . '/assets/events.css' ) );
} );
