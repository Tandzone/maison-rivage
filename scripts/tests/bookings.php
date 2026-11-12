<?php
require_once __DIR__ . '/booking-helpers.php';
global $wpdb;
$initial_user = get_current_user_id();
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
$type = get_page_by_path( 'rooms/garden-room' )->ID;
$rooms = \MaisonRivage\active_rooms( $type );
$ids = array(); $event_id = 0; $filter = null;
$rate_before = get_post_meta( $type, '_rivage_rate_cents', true );
try {
    foreach ( array( 'bookings', 'room_locks', 'occupancy', 'mail', 'rate_limits' ) as $suffix ) {
        $engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', \MaisonRivage\booking_table( $suffix ) ) );
        rivage_booking_assert( 'InnoDB' === $engine, 'Transactional table engine missing.' );
    }
    $input = rivage_test_input( $type );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_dates( array_replace( $input, array( 'check_out' => $input['check_in'] ) ) ) ), 'Zero-night stay accepted.' );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_dates( array_replace( $input, array( 'check_in' => '2026-02-30' ) ) ) ), 'Invalid calendar date accepted.' );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_dates( array_replace( $input, array( 'adults' => '0' ) ) ) ), 'Child-only party accepted.' );
    $stay = \MaisonRivage\booking_dates( $input );
    rivage_booking_assert( 2 === $stay['nights'] && str_ends_with( $stay['starts_at'], '11:00:00' ) && str_ends_with( $stay['ends_at'], '08:00:00' ), 'Madagascar arrival/departure conversion failed.' );
    rivage_booking_assert( ! \MaisonRivage\booking_capacity( $type, array_replace( $stay, array( 'children' => 1 ) ) ), 'Total guest capacity not enforced.' );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_create( $input, 'invalid' ) ), 'Unsigned request key accepted.' );
    $key = \MaisonRivage\booking_form_key();
    $a = \MaisonRivage\booking_create( $input, $key );
    rivage_booking_assert( ! is_wp_error( $a ), 'Request creation failed.' ); $ids[] = $a->id;
    $duplicate = \MaisonRivage\booking_create( $input, $key );
    rivage_booking_assert( $a->id === $duplicate->id, 'Duplicate submission created another request.' );
    rivage_booking_assert( 2 === count( \MaisonRivage\available_rooms( $type, $stay ) ), 'Unverified request blocks inventory.' );
    wp_set_current_user( $admin->ID );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_transition( $a->id, 'confirm', $rooms[0]->ID ) ), 'Unverified request confirmed.' );
    $old_token = rivage_test_token( $a->id );
    $wpdb->update( \MaisonRivage\booking_table(), array( 'token_expires' => time() - 1 ), array( 'id' => $a->id ) );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_verify( $old_token ) ), 'Expired email token accepted.' );
    \MaisonRivage\booking_resend( $a->reference, $a->email );
    $token = rivage_test_token( $a->id );
    rivage_booking_assert( $token !== $old_token && is_wp_error( \MaisonRivage\booking_verify( $old_token ) ), 'Resend did not invalidate the previous token.' );
    $a = \MaisonRivage\booking_verify( $token );
    rivage_booking_assert( 'pending' === $a->status && is_wp_error( \MaisonRivage\booking_verify( $token ) ), 'Verification not single-use.' );
    foreach ( array( 'b', 'c' ) as $name ) {
        $row = \MaisonRivage\booking_create( $input, \MaisonRivage\booking_form_key() );
        rivage_booking_assert( ! is_wp_error( $row ), 'Competing pending request rejected.' ); $ids[] = $row->id;
        $$name = \MaisonRivage\booking_verify( rivage_test_token( $row->id ) );
    }
    wp_set_current_user( 0 );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_transition( $a->id, 'confirm', $rooms[0]->ID ) ), 'Unauthenticated confirmation accepted.' );
    wp_set_current_user( $admin->ID );
    update_post_meta( $type, '_rivage_rate_cents', 99900 );
    $a = \MaisonRivage\booking_transition( $a->id, 'confirm', $rooms[0]->ID );
    rivage_booking_assert( ! is_wp_error( $a ) && 19000 === (int) $a->total_cents, 'Quoted price was not preserved.' );
    $conflict = \MaisonRivage\booking_transition( $b->id, 'confirm', $rooms[0]->ID );
    rivage_booking_assert( is_wp_error( $conflict ) && 'conflict' === $conflict->get_error_code() && 'pending' === \MaisonRivage\booking_get( $b->id )->status, 'Overlapping confirmation was not blocked atomically.' );
    $false_mail = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE event_key=%s', $b->id . ':confirmed:guest' ) );
    rivage_booking_assert( ! $false_mail, 'Failed confirmation queued a success email.' );
    $b = \MaisonRivage\booking_transition( $b->id, 'confirm', $rooms[1]->ID );
    rivage_booking_assert( ! is_wp_error( $b ) && ! \MaisonRivage\available_rooms( $type, $stay ), 'Second physical room could not be assigned.' );
    $adjacent = \MaisonRivage\booking_dates( array_replace( $input, array( 'check_in' => $input['check_out'], 'check_out' => ( new DateTimeImmutable( $input['check_out'] ) )->modify( '+1 day' )->format( 'Y-m-d' ) ) ) );
    rivage_booking_assert( 2 === count( \MaisonRivage\available_rooms( $type, $adjacent ) ), 'Checkout-day arrival blocked.' );
    \MaisonRivage\booking_transition( $a->id, 'cancel' );
    rivage_booking_assert( 1 === count( \MaisonRivage\available_rooms( $type, $stay ) ), 'Cancellation did not release occupancy.' );
    $wpdb->query( 'START TRANSACTION' ); \MaisonRivage\lock_room( $rooms[0]->ID );
    $wpdb->insert( \MaisonRivage\booking_table( 'occupancy' ), array( 'room_id' => $rooms[0]->ID, 'source_type' => 'event', 'source_id' => 999999, 'starts_at' => $stay['starts_at'], 'ends_at' => $stay['ends_at'] ) );
    $event_id = $wpdb->insert_id; $wpdb->query( 'COMMIT' );
    rivage_booking_assert( is_wp_error( \MaisonRivage\booking_transition( $c->id, 'confirm', $rooms[0]->ID ) ), 'Room event occupation ignored.' );
    $wpdb->delete( \MaisonRivage\booking_table( 'occupancy' ), array( 'id' => $event_id ) ); $event_id = 0;
    $c = \MaisonRivage\booking_transition( $c->id, 'confirm', $rooms[0]->ID );
    rivage_booking_assert( ! is_wp_error( $c ), 'Released room could not be confirmed.' );
    \MaisonRivage\booking_transition( $c->id, 'confirm', $rooms[0]->ID );
    rivage_booking_assert( 1 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE event_key=%s', $c->id . ':confirmed:guest' ) ), 'Repeated confirmation queued duplicate email.' );
    $target = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE event_key=%s', $c->id . ':confirmed:guest' ) );
    foreach ( $ids as $id ) { $wpdb->query( $wpdb->prepare( 'UPDATE ' . \MaisonRivage\booking_table( 'mail' ) . " SET status='obsolete',body='' WHERE booking_id=%d AND id<>%d", $id, $target ) ); }
    $filter = static function ( $value, $attrs ) use ( $c ) { return str_contains( $attrs['subject'], $c->reference ) ? false : $value; };
    add_filter( 'pre_wp_mail', $filter, 10, 2 );
    $wpdb->update( \MaisonRivage\booking_table( 'mail' ), array( 'attempts' => 4, 'next_attempt' => 0 ), array( 'id' => $target ) );
    \MaisonRivage\dispatch_booking_mail();
    rivage_booking_assert( 'failed' === $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE id=%d', $target ) ) && 'confirmed' === \MaisonRivage\booking_get( $c->id )->status, 'Mail failure corrupted reservation or was not tracked.' );
    remove_filter( 'pre_wp_mail', $filter, 10 ); $filter = null;
    $wpdb->update( \MaisonRivage\booking_table( 'mail' ), array( 'status' => 'queued', 'attempts' => 0, 'next_attempt' => 0 ), array( 'id' => $target ) );
    \MaisonRivage\dispatch_booking_mail();
    rivage_booking_assert( 'sent' === $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE id=%d', $target ) ), 'Mail retry failed.' );
    WP_CLI::success( 'Booking checks passed: dates, capacities, idempotency, token expiry/resend, assignment conflicts, adjacent stays, event occupation, cancellation, quoted rates and mail failure/retry.' );
} finally {
    if ( $filter ) { remove_filter( 'pre_wp_mail', $filter, 10 ); }
    if ( $event_id ) { $wpdb->delete( \MaisonRivage\booking_table( 'occupancy' ), array( 'id' => $event_id ) ); }
    rivage_test_cleanup( $ids ); update_post_meta( $type, '_rivage_rate_cents', $rate_before ); wp_set_current_user( $initial_user );
}
