<?php
require_once __DIR__ . '/booking-helpers.php';
use function MaisonRivage\event_change;
use function MaisonRivage\event_get;
use function MaisonRivage\booking_table;
use function MaisonRivage\booking_transition;
use function MaisonRivage\event_times;
global $wpdb;
$initial_user = get_current_user_id(); $events = array(); $ids = array(); $error = null;
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
$type = get_page_by_path( 'rooms/rivage-suite' )->ID;
$rooms = \MaisonRivage\active_rooms( $type );
$input = rivage_test_input( $type, 210 );
$day = $input['check_in']; $checkout = $input['check_out'];
try {
    $engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', booking_table( 'events' ) ) );
    rivage_booking_assert( 'InnoDB' === $engine, 'Event table must use InnoDB.' );
    for ( $i = 0; $i < 3; $i++ ) { $events[] = wp_insert_post( array( 'post_type' => 'rivage_event', 'post_status' => 'draft', 'post_title' => 'Event integration fixture ' . $i ) ); }
    $a = $events[0]; $b = $events[1];
    $suite = array( 'start' => $day . 'T18:00', 'end' => $day . 'T20:00', 'venue' => 'suite', 'room_id' => $rooms[0]->ID );
    wp_set_current_user( 0 );
    rivage_booking_assert( is_wp_error( event_change( $a, 'schedule', $suite ) ), 'Anonymous schedule write allowed.' );
    wp_set_current_user( $admin->ID );
    rivage_booking_assert( is_wp_error( event_times( '2026-02-30T18:00', '2026-03-01T19:00' ) ), 'Invalid date accepted.' );
    rivage_booking_assert( is_wp_error( event_times( $day . 'T18:00', $day . 'T17:00' ) ), 'Reverse interval accepted.' );
    $garden = \MaisonRivage\active_rooms( get_page_by_path( 'rooms/garden-room' )->ID )[0]->ID;
    rivage_booking_assert( is_wp_error( event_change( $a, 'schedule', array_replace( $suite, array( 'room_id' => $garden ) ), 0 ) ), 'Garden room accepted for an event.' );
    $first = event_change( $a, 'schedule', $suite, 0 );
    rivage_booking_assert( ! is_wp_error( $first ) && str_ends_with( $first->occupied_from, '14:00:00' ) && str_ends_with( $first->occupied_until, '18:00:00' ), 'Buffer or UTC conversion wrong.' );
    rivage_booking_assert( is_wp_error( event_change( $a, 'schedule', $suite, 0 ) ), 'Stale revision overwritten.' );
    $collision = event_change( $b, 'schedule', $suite, 0 );
    rivage_booking_assert( is_wp_error( $collision ) && 'conflict' === $collision->get_error_code(), 'Event/event overlap accepted.' );
    $hall = event_change( $b, 'schedule', array_replace( $suite, array( 'venue' => 'hall' ) ), 0 );
    rivage_booking_assert( ! is_wp_error( $hall ) && 0 === (int) $hall->room_id, 'Hall schedule failed.' );
    $hall_conflict = event_change( $b, 'schedule', $suite, $hall->revision );
    rivage_booking_assert( is_wp_error( $hall_conflict ) && 'hall' === event_get( $b )->venue, 'Failed venue edit changed previous schedule.' );
    $booking = \MaisonRivage\booking_create( $input, \MaisonRivage\booking_form_key() );
    rivage_booking_assert( ! is_wp_error( $booking ), 'Booking fixture failed.' ); $ids[] = $booking->id;
    \MaisonRivage\booking_verify( rivage_test_token( $booking->id ) );
    $blocked = booking_transition( $booking->id, 'confirm', $rooms[0]->ID );
    rivage_booking_assert( is_wp_error( $blocked ) && 'conflict' === $blocked->get_error_code(), 'Booking ignored suite event.' );
    $confirmed = booking_transition( $booking->id, 'confirm', $rooms[1]->ID );
    rivage_booking_assert( ! is_wp_error( $confirmed ), 'Alternative suite booking failed.' );
    $before = event_get( $a );
    $edit = event_change( $a, 'schedule', array_replace( $suite, array( 'room_id' => $rooms[1]->ID ) ), $before->revision );
    rivage_booking_assert( is_wp_error( $edit ) && (int) event_get( $a )->room_id === (int) $rooms[0]->ID, 'Conflicting room edit lost old occupation.' );
    // Checkout 11:00 + preparation means a 12:00 event starts at the exact free boundary.
    $edge = array_replace( $suite, array( 'room_id' => $rooms[1]->ID, 'start' => $checkout . 'T12:00', 'end' => $checkout . 'T13:00' ) );
    $saved = event_change( $events[2], 'schedule', $edge, 0 );
    rivage_booking_assert( ! is_wp_error( $saved ), 'Exact checkout/buffer boundary rejected.' );
    $bad_edge = event_change( $events[2], 'schedule', array_replace( $edge, array( 'start' => $checkout . 'T11:59' ) ), $saved->revision );
    rivage_booking_assert( is_wp_error( $bad_edge ) && event_get( $events[2] )->starts_at === $saved->starts_at, 'Preparation overlap accepted or old time lost.' );
    $cancel = event_change( $a, 'cancel', array(), $before->revision );
    rivage_booking_assert( ! is_wp_error( $cancel ) && ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . booking_table( 'occupancy' ) . " WHERE source_type='event' AND source_id=%d", $a ) ), 'Cancellation did not release event occupancy.' );
    $again = event_change( $a, 'schedule', $suite, $cancel->revision );
    rivage_booking_assert( ! is_wp_error( $again ), 'Reschedule after cancellation failed.' );
    wp_trash_post( $a );
    rivage_booking_assert( 'archived' === event_get( $a )->status && ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . booking_table( 'occupancy' ) . " WHERE source_type='event' AND source_id=%d", $a ) ), 'Trash did not atomically release occupancy.' );
    rivage_booking_assert( is_wp_error( event_change( $a, 'schedule', $suite ) ), 'Archived event scheduled.' );
    wp_untrash_post( $a );
    rivage_booking_assert( 'cancelled' === event_get( $a )->status, 'Restore silently reinstated occupancy.' );
    // Public catalogue excludes unpublished operational events and cancelled records.
    $html = \MaisonRivage\events_shortcode( array( 'limit' => 12 ) );
    rivage_booking_assert( ! str_contains( $html, 'Event integration fixture' ), 'Draft operational event leaked publicly.' );
} catch ( Throwable $exception ) { $error = $exception->getMessage(); }
finally {
    wp_set_current_user( $admin->ID );
    foreach ( $events as $id ) { wp_delete_post( $id, true ); $wpdb->delete( booking_table( 'events' ), array( 'event_id' => $id ) ); }
    rivage_test_cleanup( $ids ); wp_set_current_user( $initial_user );
}
if ( $error ) { WP_CLI::error( $error ); }
WP_CLI::success( 'Event checks passed: strict times, buffers, permissions, eligible suites, stale edits, event/booking conflicts in both directions, atomic rollback, checkout boundary, cancellation, trash/restore and draft privacy.' );
