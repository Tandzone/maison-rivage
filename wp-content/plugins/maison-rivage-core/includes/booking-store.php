<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;

function booking_table( $suffix = 'bookings' ) { global $wpdb; return $wpdb->prefix . 'rivage_' . $suffix; }
function booking_schema() {
    if ( '2' === get_option( 'rivage_booking_schema' ) ) { return; }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    $tables = array(
        'bookings' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
reference varchar(16) NOT NULL,
request_key varchar(64) NOT NULL,
type_id bigint(20) unsigned NOT NULL,
room_id bigint(20) unsigned DEFAULT NULL,
check_in date NOT NULL,
check_out date NOT NULL,
starts_at datetime NOT NULL,
ends_at datetime NOT NULL,
adults smallint(5) unsigned NOT NULL,
children smallint(5) unsigned NOT NULL,
guest_name varchar(120) NOT NULL,
email varchar(254) NOT NULL,
message text NOT NULL,
rate_cents int(10) unsigned NOT NULL,
total_cents int(10) unsigned NOT NULL,
status varchar(20) NOT NULL,
token_hash varchar(64) DEFAULT NULL,
token_expires bigint(20) unsigned DEFAULT NULL,
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY reference (reference),
UNIQUE KEY request_key (request_key),
KEY token_hash (token_hash),
KEY status_dates (status,check_in,check_out)",
        'room_locks' => "room_id bigint(20) unsigned NOT NULL,
PRIMARY KEY  (room_id)",
        'occupancy' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
room_id bigint(20) unsigned NOT NULL,
booking_id bigint(20) unsigned DEFAULT NULL,
source_type varchar(20) NOT NULL DEFAULT 'booking',
source_id bigint(20) unsigned DEFAULT NULL,
starts_at datetime NOT NULL,
ends_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY booking_id (booking_id),
KEY room_dates (room_id,starts_at,ends_at)",
        'mail' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
booking_id bigint(20) unsigned NOT NULL,
event_key varchar(100) NOT NULL,
recipient varchar(254) NOT NULL,
subject varchar(200) NOT NULL,
body longtext NOT NULL,
status varchar(20) NOT NULL DEFAULT 'queued',
attempts smallint(5) unsigned NOT NULL DEFAULT 0,
next_attempt bigint(20) unsigned NOT NULL DEFAULT 0,
created_at bigint(20) unsigned NOT NULL DEFAULT 0,
PRIMARY KEY  (id),
UNIQUE KEY event_key (event_key),
KEY status (status)",
        'rate_limits' => "bucket varchar(64) NOT NULL,
hits int(10) unsigned NOT NULL DEFAULT 1,
expires bigint(20) unsigned NOT NULL,
PRIMARY KEY  (bucket),
KEY expires (expires)",
    );
    foreach ( $tables as $name => $columns ) {
        dbDelta( 'CREATE TABLE ' . booking_table( $name ) . " (\n" . $columns . "\n) ENGINE=InnoDB $charset;" );
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( booking_table( $name ) ) ) ) !== booking_table( $name ) ) { return; }
    }
    $wpdb->query( $wpdb->prepare( 'UPDATE ' . booking_table( 'mail' ) . ' SET created_at=%d WHERE created_at=0', time() ) );
    update_option( 'rivage_booking_schema', '2', false );
}
function booking_get( $id, $lock = false ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table() . ' WHERE id=%d' . ( $lock ? ' FOR UPDATE' : '' ), $id ) );
}
function booking_error( $code, $text ) { return new \WP_Error( $code, $text ); }
function booking_dates( $input ) {
    $zone = new \DateTimeZone( 'Indian/Antananarivo' );
    $dates = array();
    foreach ( array( 'check_in', 'check_out' ) as $key ) {
        $value = $input[ $key ] ?? '';
        if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $value ) ) { return booking_error( 'dates', 'Choose valid arrival and departure dates.' ); }
        $date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $zone );
        if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) { return booking_error( 'dates', 'Choose valid arrival and departure dates.' ); }
        $dates[ $key ] = $date;
    }
    $today = new \DateTimeImmutable( 'today', $zone );
    $nights = (int) $dates['check_in']->diff( $dates['check_out'] )->format( '%r%a' );
    if ( $dates['check_in'] < $today || $dates['check_in'] > $today->modify( '+1 year' ) || $nights < 1 || $nights > 30 ) {
        return booking_error( 'dates', 'Choose a future arrival within one year and a stay of 1 to 30 nights.' );
    }
    foreach ( array( 'adults' => array( 1, 12 ), 'children' => array( 0, 12 ) ) as $key => $range ) {
        $value = $input[ $key ] ?? ( 'children' === $key ? '0' : '' );
        if ( ! is_scalar( $value ) || ! preg_match( '/^\d+$/D', (string) $value ) || (int) $value < $range[0] || (int) $value > $range[1] ) {
            return booking_error( 'guests', 'Include at least one adult and valid guest counts.' );
        }
        $input[ $key ] = (int) $value;
    }
    return array( 'check_in' => $dates['check_in']->format( 'Y-m-d' ), 'check_out' => $dates['check_out']->format( 'Y-m-d' ),
        'starts_at' => $dates['check_in']->setTime( 14, 0 )->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
        'ends_at' => $dates['check_out']->setTime( 11, 0 )->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
        'adults' => $input['adults'], 'children' => $input['children'], 'nights' => $nights );
}
function booking_capacity( $type, $stay ) {
    return is_room_type( $type ) && $stay['adults'] <= (int) get_post_meta( $type, '_rivage_max_adults', true )
        && $stay['children'] <= (int) get_post_meta( $type, '_rivage_max_children', true )
        && $stay['adults'] + $stay['children'] <= (int) get_post_meta( $type, '_rivage_capacity', true );
}
function active_rooms( $type ) {
    return get_posts( array( 'post_type' => 'rivage_room', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC',
        'meta_query' => array( array( 'key' => '_rivage_type_id', 'value' => $type ), array( 'key' => '_rivage_status', 'value' => 'active' ) ) ) );
}
function occupancy_conflict( $room, $start, $end ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table( 'occupancy' ) . ' WHERE room_id=%d AND starts_at < %s AND ends_at > %s LIMIT 1', $room, $end, $start ) );
}
function available_rooms( $type, $stay ) {
    if ( ! booking_capacity( $type, $stay ) ) { return array(); }
    return array_values( array_filter( active_rooms( $type ), static function ( $room ) use ( $stay ) {
        return ! occupancy_conflict( $room->ID, $stay['starts_at'], $stay['ends_at'] );
    } ) );
}
function booking_limit( $scope, $identity, $max = 5, $seconds = 3600 ) {
    global $wpdb;
    $window = (int) floor( time() / $seconds );
    $key = hash_hmac( 'sha256', $scope . ':' . $identity . ':' . $window, wp_salt( 'auth' ) );
    $table = booking_table( 'rate_limits' );
    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO $table (bucket,hits,expires) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE hits=hits+1", $key, ( $window + 1 ) * $seconds ) );
    return false !== $result && (int) $wpdb->get_var( $wpdb->prepare( "SELECT hits FROM $table WHERE bucket=%s", $key ) ) <= $max;
}
function booking_quote( $type, $stay ) {
    $rate = get_post_meta( $type, '_rivage_rate_cents', true );
    return '' !== $rate && (int) $rate > 0 ? array( 'rate_cents' => (int) $rate, 'total_cents' => (int) $rate * $stay['nights'] ) : null;
}
function booking_form_key() {
    $key = time() . ':' . bin2hex( random_bytes( 16 ) );
    return $key . ':' . hash_hmac( 'sha256', $key, wp_salt( 'nonce' ) );
}
function booking_valid_key( $key ) {
    if ( ! is_string( $key ) || ! preg_match( '/^(\d+):([a-f0-9]{32}):([a-f0-9]{64})$/D', $key, $m ) ) { return false; }
    return (int) $m[1] <= time() && (int) $m[1] >= time() - 7200
        && hash_equals( hash_hmac( 'sha256', $m[1] . ':' . $m[2], wp_salt( 'nonce' ) ), $m[3] );
}
function booking_create( $input, $key ) {
    global $wpdb;
    if ( ! booking_valid_key( $key ) ) { return booking_error( 'form', 'This form expired. Reload the page and try again.' ); }
    $digest = hash( 'sha256', $key );
    $existing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table() . ' WHERE request_key=%s', $digest ) );
    if ( $existing ) { return $existing; }
    $stay = booking_dates( $input );
    if ( is_wp_error( $stay ) ) { return $stay; }
    $type = isset( $input['type_id'] ) && is_scalar( $input['type_id'] ) ? absint( $input['type_id'] ) : 0;
    if ( ! booking_capacity( $type, $stay ) ) { return booking_error( 'capacity', 'Choose a room category that fits your party.' ); }
    $quote = booking_quote( $type, $stay );
    if ( isset( $input['quoted_rates'] ) && ( ! is_array( $input['quoted_rates'] ) || ! isset( $input['quoted_rates'][ $type ] )
        || ! is_scalar( $input['quoted_rates'][ $type ] ) || ! $quote || (string) $quote['rate_cents'] !== (string) $input['quoted_rates'][ $type ] ) ) {
        return booking_error( 'quote', 'The nightly rate changed. Search again to review the new total before sending a request.' );
    }
    if ( ! $quote || ! available_rooms( $type, $stay ) ) { return booking_error( 'availability', 'This category is no longer available. Search again or choose another category.' ); }
    foreach ( array( 'guest_name', 'email', 'message' ) as $field ) {
        if ( isset( $input[ $field ] ) && ! is_string( $input[ $field ] ) ) { return booking_error( 'guest', 'Provide valid guest details.' ); }
    }
    $name = sanitize_text_field( $input['guest_name'] ?? '' );
    $email = trim( $input['email'] ?? '' );
    $message = sanitize_textarea_field( $input['message'] ?? '' );
    if ( '' === $name || mb_strlen( $name ) > 120 || ! is_email( $email ) || strlen( $email ) > 254 || mb_strlen( $message ) > 2000 ) { return booking_error( 'guest', 'Provide your name, a valid email address and a message of at most 2,000 characters.' ); }
    if ( empty( $input['demo_ack'] ) ) { return booking_error( 'policy', 'Acknowledge the demo and manual confirmation before sending your request.' ); }
    $reference = strtoupper( bin2hex( random_bytes( 6 ) ) );
    $token = bin2hex( random_bytes( 32 ) );
    $now = gmdate( 'Y-m-d H:i:s' );
    $wpdb->query( 'START TRANSACTION' );
    $saved = $wpdb->insert( booking_table(), array_merge( array_diff_key( $stay, array( 'nights' => true ) ), $quote, array(
        'reference' => $reference, 'request_key' => $digest, 'type_id' => $type, 'guest_name' => $name, 'email' => $email,
        'message' => $message, 'status' => 'unverified', 'token_hash' => hash( 'sha256', $token ), 'token_expires' => time() + 1800,
        'created_at' => $now, 'updated_at' => $now ) ) );
    if ( false === $saved ) {
        $wpdb->query( 'ROLLBACK' );
        $existing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table() . ' WHERE request_key=%s', $digest ) );
        return $existing ?: booking_error( 'storage', 'Your request could not be saved. Please try again.' );
    }
    $id = $wpdb->insert_id;
    if ( ! queue_booking_mail( $id, 'verify:' . hash( 'sha256', $token ), $email, 'Verify your Maison Rivage request', verification_body( $reference, $token ) ) ) {
        $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'Your request could not be saved. Please try again.' );
    }
    if ( false === $wpdb->query( 'COMMIT' ) ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'Your request could not be saved.' ); }
    return booking_get( $id );
}
function booking_verify( $token ) {
    global $wpdb;
    if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { return booking_error( 'token', 'This verification link is invalid or has already been used.' ); }
    $wpdb->query( 'START TRANSACTION' );
    $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table() . ' WHERE token_hash=%s FOR UPDATE', hash( 'sha256', $token ) ) );
    if ( ! $row || 'unverified' !== $row->status || (int) $row->token_expires < time() ) {
        $wpdb->query( 'ROLLBACK' ); return booking_error( 'token', 'This link expired, is invalid, or has already been used. You can request a new verification email.' );
    }
    $ok = $wpdb->update( booking_table(), array( 'status' => 'pending', 'token_hash' => null, 'token_expires' => null, 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ), array( 'id' => $row->id ) );
    $queued = queue_booking_mail( $row->id, 'pending:admin', get_option( 'admin_email' ), 'Verified reservation request — ' . $row->reference,
        "A verified request is awaiting review.\nReference: $row->reference\n" . admin_url( 'admin.php?page=rivage-bookings&booking=' . $row->id ) );
    if ( false === $ok || ! $queued ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'Verification could not be saved. Please try again.' ); }
    if ( false === $wpdb->query( 'COMMIT' ) ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'Verification could not be saved.' ); }
    return booking_get( $row->id );
}
function booking_resend( $reference, $email ) {
    global $wpdb;
    $wpdb->query( 'START TRANSACTION' );
    $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table() . ' WHERE reference=%s AND email=%s FOR UPDATE', $reference, $email ) );
    if ( ! $row || 'unverified' !== $row->status ) { $wpdb->query( 'ROLLBACK' ); return; }
    $token = bin2hex( random_bytes( 32 ) );
    if ( false === $wpdb->update( booking_table(), array( 'token_hash' => hash( 'sha256', $token ), 'token_expires' => time() + 1800 ), array( 'id' => $row->id ) ) ) { $wpdb->query( 'ROLLBACK' ); return; }
    $wpdb->query( $wpdb->prepare( 'UPDATE ' . booking_table( 'mail' ) . " SET status='obsolete',body='' WHERE booking_id=%d AND event_key LIKE 'verify:%%' AND status='queued'", $row->id ) );
    if ( ! queue_booking_mail( $row->id, 'verify:' . hash( 'sha256', $token ), $row->email, 'Verify your Maison Rivage request', verification_body( $row->reference, $token ) ) ) { $wpdb->query( 'ROLLBACK' ); return; }
    $wpdb->query( 'COMMIT' );
}
/** Every room occupancy writer (including future events) must hold this lock. */
function lock_room( $room_id ) {
    global $wpdb;
    $table = booking_table( 'room_locks' );
    // The upsert takes an exclusive lock even when the row already exists, avoiding shared-lock upgrades.
    if ( false === $wpdb->query( $wpdb->prepare( "INSERT INTO $table (room_id) VALUES (%d) ON DUPLICATE KEY UPDATE room_id=VALUES(room_id)", $room_id ) ) ) { return false; }
    return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT room_id FROM $table WHERE room_id=%d FOR UPDATE", $room_id ) );
}
function booking_transition( $id, $action, $room_id = 0 ) {
    global $wpdb;
    if ( ! current_user_can( 'manage_options' ) ) { return booking_error( 'permission', 'You cannot manage reservations.' ); }
    if ( ! in_array( $action, array( 'confirm', 'decline', 'cancel' ), true ) ) { return booking_error( 'action', 'Invalid booking action.' ); }
    $wpdb->query( 'START TRANSACTION' );
    $row = booking_get( $id, true );
    if ( ! $row ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'missing', 'Request not found.' ); }
    $target = array( 'confirm' => 'confirmed', 'decline' => 'declined', 'cancel' => 'cancelled' )[ $action ];
    if ( $row->status === $target ) { $wpdb->query( 'ROLLBACK' ); return $row; }
    $allowed = 'cancel' === $action ? array( 'pending', 'confirmed' ) : array( 'pending' );
    if ( ! in_array( $row->status, $allowed, true ) ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'state', 'This action is not available for the current request status.' ); }
    if ( 'confirm' === $action ) {
        if ( ! lock_room( $room_id ) ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'lock', 'This room could not be locked. Try again.' ); }
        do_action( 'rivage_booking_room_locked', $room_id, $id );
        clean_post_cache( $room_id );
        $stay = booking_dates( (array) $row );
        if ( is_wp_error( $stay ) || ! booking_capacity( $row->type_id, $stay ) || 'rivage_room' !== get_post_type( $room_id )
            || 'publish' !== get_post_status( $room_id ) || 'active' !== get_post_meta( $room_id, '_rivage_status', true )
            || (int) get_post_meta( $room_id, '_rivage_type_id', true ) !== (int) $row->type_id ) {
            $wpdb->query( 'ROLLBACK' ); return booking_error( 'room', 'Choose an active room in this category that still fits the party and dates.' );
        }
        $conflict = occupancy_conflict( $room_id, $row->starts_at, $row->ends_at );
        if ( $conflict ) {
            $wpdb->query( 'ROLLBACK' );
            return booking_error( 'conflict', 'Confirmation blocked: this room is occupied by ' . $conflict->source_type . ' #' . ( $conflict->booking_id ?: $conflict->source_id ) . '. Choose another room.' );
        }
        $ok = $wpdb->insert( booking_table( 'occupancy' ), array( 'room_id' => $room_id, 'booking_id' => $id, 'source_type' => 'booking', 'starts_at' => $row->starts_at, 'ends_at' => $row->ends_at ) );
        if ( false === $ok ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'The room could not be reserved. Try again.' ); }
    } elseif ( $row->room_id ) {
        if ( ! lock_room( $row->room_id ) ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'lock', 'Please try again.' ); }
        if ( false === $wpdb->delete( booking_table( 'occupancy' ), array( 'booking_id' => $id ) ) ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'Cancellation could not be saved.' ); }
    }
    $changes = array( 'status' => $target, 'updated_at' => gmdate( 'Y-m-d H:i:s' ) );
    if ( 'confirm' === $action ) { $changes['room_id'] = $room_id; }
    $ok = $wpdb->update( booking_table(), $changes, array( 'id' => $id ) );
    $subject = 'Maison Rivage request ' . $target . ' — ' . $row->reference;
    $body = booking_summary( $row ) . "\nStatus: $target\n";
    if ( 'confirmed' === $target ) { $body .= "Arrival from 14:00; departure before 11:00 (Madagascar time).\n"; }
    $body .= "This is a fictional hotel demonstration. No real accommodation or payment is involved.\nFor changes, reply to this email.";
    $queued = queue_booking_mail( $id, $target . ':guest', $row->email, $subject, $body );
    if ( false === $ok || ! $queued ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'The change could not be saved. Try again.' ); }
    if ( false === $wpdb->query( 'COMMIT' ) ) { $wpdb->query( 'ROLLBACK' ); return booking_error( 'storage', 'The change could not be saved.' ); }
    return booking_get( $id );
}
