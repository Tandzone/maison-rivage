<?php
if ( 'local' !== wp_get_environment_type() ) { WP_CLI::error( 'Booking tests require the local environment.' ); }
function rivage_booking_assert( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
function rivage_test_input( $type, $offset = 150 ) {
    $date = new DateTimeImmutable( 'today +' . $offset . ' days', new DateTimeZone( 'Indian/Antananarivo' ) );
    return array( 'type_id' => $type, 'check_in' => $date->format( 'Y-m-d' ), 'check_out' => $date->modify( '+2 days' )->format( 'Y-m-d' ),
        'adults' => '2', 'children' => '0', 'guest_name' => 'Booking Integration Test', 'email' => 'booking-integration@example.test', 'message' => 'Local automated test.', 'demo_ack' => '1' );
}
function rivage_test_token( $id ) {
    global $wpdb;
    $body = $wpdb->get_var( $wpdb->prepare( 'SELECT body FROM ' . \MaisonRivage\booking_table( 'mail' ) . " WHERE booking_id=%d AND event_key LIKE '%%:verify:%%' ORDER BY id DESC LIMIT 1", $id ) );
    preg_match( '/token=([a-f0-9]{64})/', $body, $matches );
    return $matches[1] ?? '';
}
function rivage_test_cleanup( $ids ) {
    global $wpdb;
    foreach ( $ids as $id ) {
        $wpdb->delete( \MaisonRivage\booking_table( 'occupancy' ), array( 'booking_id' => $id ) );
        $wpdb->delete( \MaisonRivage\booking_table( 'mail' ), array( 'booking_id' => $id ) );
        $wpdb->delete( \MaisonRivage\booking_table(), array( 'id' => $id ) );
    }
}
