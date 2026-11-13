<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;
function booking_summary( $row ) {
    return "Maison Rivage\nReference: $row->reference\nRoom category: " . get_the_title( $row->type_id ) . "\nDates: $row->check_in to $row->check_out\nGuests: $row->adults adults, $row->children children\nDemo total: " . format_rate( (int) $row->total_cents ) . "\n";
}
function verification_body( $reference, $token ) {
    return "Verify your email address for request $reference:\n" . add_query_arg( 'token', $token, home_url( '/booking/verify/' ) )
        . "\nOpen the link and press Verify Email. It expires in 30 minutes. Verification does not reserve a room; the hotel must confirm your stay.\nThis is a fictional hotel demonstration. No real booking or payment is involved.";
}
function queue_booking_mail( $id, $event, $recipient, $subject, $body ) {
    global $wpdb;
    return false !== $wpdb->insert( booking_table( 'mail' ), array( 'booking_id' => $id, 'event_key' => $id . ':' . $event,
        'recipient' => $recipient, 'subject' => $subject, 'body' => $body, 'status' => 'queued', 'next_attempt' => time(), 'created_at' => time() ) );
}
function dispatch_booking_mail() {
    global $wpdb;
    $table = booking_table( 'mail' );
    $now = time();
    $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='queued' WHERE status='sending' AND next_attempt < %d", $now ) );
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE status='queued' AND next_attempt <= %d ORDER BY id LIMIT 10", $now ) );
    foreach ( $rows as $row ) {
        if ( str_contains( $row->event_key, ':verify:' ) ) {
            $booking = booking_get( $row->booking_id );
            if ( ! $booking || ! $booking->token_hash || ! str_ends_with( $row->event_key, $booking->token_hash ) || $booking->token_expires < $now ) {
                $wpdb->update( $table, array( 'status' => 'obsolete', 'body' => '' ), array( 'id' => $row->id ) ); continue;
            }
        }
        $claim = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='sending',next_attempt=%d WHERE id=%d AND status='queued'", $now + 300, $row->id ) );
        if ( 1 !== $claim ) { continue; }
        $ok = wp_mail( $row->recipient, $row->subject, $row->body,
            array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . get_option( 'admin_email' ) ) );
        $attempts = (int) $row->attempts + 1;
        $changes = array( 'attempts' => $attempts, 'status' => $ok ? 'sent' : ( $attempts >= 5 ? 'failed' : 'queued' ),
            'next_attempt' => $now + min( 3600, 60 * ( 2 ** $attempts ) ) );
        if ( $ok ) { $changes['body'] = ''; }
        $wpdb->update( $table, $changes, array( 'id' => $row->id ) );
    }
    $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . booking_table( 'rate_limits' ) . ' WHERE expires < %d', $now ) );
}
add_action( 'rivage_mail_tick', __NAMESPACE__ . '\\dispatch_booking_mail' );
add_filter( 'cron_schedules', static function ( $schedules ) {
    $schedules['rivage_minute'] = array( 'interval' => 60, 'display' => 'Every minute (Maison Rivage)' );
    return $schedules;
} );
add_action( 'init', static function () {
    if ( 'rivage_minute' !== wp_get_schedule( 'rivage_mail_tick' ) ) {
        wp_clear_scheduled_hook( 'rivage_mail_tick' );
        wp_schedule_event( time() + 60, 'rivage_minute', 'rivage_mail_tick' );
    }
} );

function booking_cleanup() {
    global $wpdb;
    $rows = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . booking_table() . " WHERE (status='unverified' AND created_at<%s) OR ends_at<%s LIMIT 100",
        gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS ) ) );
    foreach ( $rows as $id ) {
        $wpdb->query( 'START TRANSACTION' );
        $row = booking_get( $id, true );
        if ( ! $row || ( ! ( 'unverified' === $row->status && strtotime( $row->created_at . ' UTC' ) < time() - DAY_IN_SECONDS ) && strtotime( $row->ends_at . ' UTC' ) >= time() - 90 * DAY_IN_SECONDS ) ) { $wpdb->query( 'ROLLBACK' ); continue; }
        if ( $row->room_id && ! lock_room( $row->room_id ) ) { $wpdb->query( 'ROLLBACK' ); continue; }
        $a = $wpdb->delete( booking_table( 'mail' ), array( 'booking_id' => $id ) );
        $b = $wpdb->delete( booking_table( 'occupancy' ), array( 'booking_id' => $id ) );
        $c = $wpdb->delete( booking_table(), array( 'id' => $id ) );
        $wpdb->query( false === $a || false === $b || false === $c ? 'ROLLBACK' : 'COMMIT' );
    }
}
add_action( 'rivage_mail_tick', __NAMESPACE__ . '\\booking_cleanup' );
