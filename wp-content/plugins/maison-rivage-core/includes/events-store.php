<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;

function register_events() {
    $caps = array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_options' );
    register_post_type( 'rivage_event', array(
        'labels' => array( 'name' => 'Events', 'singular_name' => 'Event', 'add_new_item' => 'Add Event', 'edit_item' => 'Edit Event' ),
        'public' => true, 'show_in_rest' => true, 'has_archive' => false, 'show_in_menu' => 'maison-rivage',
        'rewrite' => array( 'slug' => 'events', 'with_front' => false ), 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
        'capabilities' => $caps, 'map_meta_cap' => false,
    ) );
}
function event_schema() {
    if ( '1' === get_option( 'rivage_event_schema' ) ) { return; }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = booking_table( 'events' );
    $charset = $wpdb->get_charset_collate();
    dbDelta( "CREATE TABLE $table (
 event_id bigint(20) unsigned NOT NULL,
 status varchar(20) NOT NULL DEFAULT 'unscheduled',
 venue varchar(20) NOT NULL DEFAULT 'hall',
 room_id bigint(20) unsigned NOT NULL DEFAULT 0,
 starts_at datetime DEFAULT NULL,
 ends_at datetime DEFAULT NULL,
 occupied_from datetime DEFAULT NULL,
 occupied_until datetime DEFAULT NULL,
 revision bigint(20) unsigned NOT NULL DEFAULT 0,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (event_id),
 KEY status_dates (status,ends_at)
) ENGINE=InnoDB $charset;" );
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) { update_option( 'rivage_event_schema', '1', false ); }
}
function event_get( $id, $lock = false ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table( 'events' ) . ' WHERE event_id=%d' . ( $lock ? ' FOR UPDATE' : '' ), $id ) );
}
function event_times( $start, $end ) {
    $tz = new \DateTimeZone( 'Indian/Antananarivo' );
    $dates = array();
    foreach ( array( $start, $end ) as $value ) {
        if ( ! is_string( $value ) ) { return booking_error( 'dates', 'Enter valid local start and end times.' ); }
        $date = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, $tz );
        if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $value ) { return booking_error( 'dates', 'Enter valid local start and end times.' ); }
        $dates[] = $date;
    }
    if ( $dates[1] <= $dates[0] || $dates[1]->getTimestamp() - $dates[0]->getTimestamp() > DAY_IN_SECONDS ) { return booking_error( 'dates', 'An event must end after it starts and last no more than 24 hours.' ); }
    $utc = new \DateTimeZone( 'UTC' );
    return array( 'starts_at' => $dates[0]->setTimezone( $utc )->format( 'Y-m-d H:i:s' ), 'ends_at' => $dates[1]->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
        'occupied_from' => $dates[0]->modify( '-1 hour' )->setTimezone( $utc )->format( 'Y-m-d H:i:s' ), 'occupied_until' => $dates[1]->modify( '+1 hour' )->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) );
}
function event_room_valid( $id ) {
    if ( 'rivage_room' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) || 'active' !== get_post_meta( $id, '_rivage_status', true ) ) { return false; }
    $type = (int) get_post_meta( $id, '_rivage_type_id', true );
    return type_data( $type ) && (bool) get_post_meta( $type, '_rivage_private_events', true );
}
/** A single transaction owns schedule replacement and inventory; editorial saves never write occupancy. */
function event_change( $id, $action, $input = array(), $revision = null, $system = false ) {
    global $wpdb;
    if ( ! $system && ! current_user_can( 'manage_options' ) ) { return booking_error( 'permission', 'You cannot manage event schedules.' ); }
    if ( ( ! $system && in_array( $action, array( 'archive', 'restore' ), true ) ) || ! in_array( $action, array( 'schedule', 'cancel', 'archive', 'restore' ), true ) || ( $system && ! in_array( $action, array( 'archive', 'restore' ), true ) ) ) { return booking_error( 'action', 'Invalid event action.' ); }
    $post = get_post( $id );
    if ( ! $post || 'rivage_event' !== $post->post_type ) { return booking_error( 'event', 'Event not found.' ); }
    $times = array();
    if ( 'schedule' === $action ) {
        $times = event_times( $input['start'] ?? '', $input['end'] ?? '' );
        if ( is_wp_error( $times ) ) { return $times; }
        if ( ! in_array( $input['venue'] ?? '', array( 'hall', 'suite' ), true ) ) { return booking_error( 'venue', 'Select Hall or a Rivage Suite.' ); }
    }
    $table = booking_table( 'events' );
    $wpdb->query( 'START TRANSACTION' );
    $fail = static function ( $code, $message ) use ( $wpdb ) { $wpdb->query( 'ROLLBACK' ); return booking_error( $code, $message ); };
    // An exclusive upsert serializes first-time saves as well as subsequent edits.
    if ( false === $wpdb->query( $wpdb->prepare( "INSERT INTO $table (event_id,updated_at) VALUES (%d,%s) ON DUPLICATE KEY UPDATE event_id=VALUES(event_id)", $id, gmdate( 'Y-m-d H:i:s' ) ) ) ) { return $fail( 'storage', 'The schedule could not be locked.' ); }
    $old = event_get( $id, true );
    if ( ! $old ) { return $fail( 'storage', 'The schedule could not be read.' ); }
    if ( null !== $revision && (int) $revision !== (int) $old->revision ) { return $fail( 'stale', 'This schedule changed in another session. Reload it before saving.' ); }
    if ( 'schedule' === $action && ( 'archived' === $old->status || 'trash' === $post->post_status ) ) { return $fail( 'archived', 'Restore this event before scheduling it.' ); }
    if ( 'cancel' === $action && 'archived' === $old->status ) { return $fail( 'archived', 'Restore this event first.' ); }
    $room = 'schedule' === $action && 'suite' === $input['venue'] ? absint( $input['room_id'] ?? 0 ) : 0;
    $locks = array_unique( array_filter( array( (int) $old->room_id, $room ) ) ); sort( $locks, SORT_NUMERIC );
    foreach ( $locks as $room_id ) { if ( ! lock_room( $room_id ) ) { return $fail( 'lock', 'A room is being updated. Please try again.' ); } }
    if ( $room ) {
        clean_post_cache( $room );
        if ( ! event_room_valid( $room ) ) { return $fail( 'room', 'Select an active Rivage Suite eligible for private events.' ); }
        do_action( 'rivage_event_room_locked', $room, $id );
        $conflict = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . booking_table( 'occupancy' ) . " WHERE room_id=%d AND starts_at<%s AND ends_at>%s AND NOT(source_type='event' AND source_id=%d) LIMIT 1", $room, $times['occupied_until'], $times['occupied_from'], $id ) );
        if ( $conflict ) { return $fail( 'conflict', 'Schedule blocked: this suite is occupied by ' . $conflict->source_type . ' #' . ( $conflict->booking_id ?: $conflict->source_id ) . '. The previous schedule has been preserved.' ); }
    }
    if ( false === $wpdb->delete( booking_table( 'occupancy' ), array( 'source_type' => 'event', 'source_id' => $id ) ) ) { return $fail( 'storage', 'The previous occupation could not be updated.' ); }
    $changes = array( 'status' => array( 'schedule' => 'scheduled', 'cancel' => 'cancelled', 'archive' => 'archived', 'restore' => 'cancelled' )[ $action ], 'revision' => (int) $old->revision + 1, 'updated_at' => gmdate( 'Y-m-d H:i:s' ) );
    if ( 'schedule' === $action ) {
        $changes = array_merge( $changes, $times, array( 'venue' => $input['venue'], 'room_id' => $room ) );
        if ( $room && false === $wpdb->insert( booking_table( 'occupancy' ), array( 'room_id' => $room, 'source_type' => 'event', 'source_id' => $id, 'starts_at' => $times['occupied_from'], 'ends_at' => $times['occupied_until'] ) ) ) { return $fail( 'storage', 'The suite could not be reserved for this event.' ); }
    }
    if ( false === $wpdb->update( $table, $changes, array( 'event_id' => $id ) ) || false === $wpdb->query( 'COMMIT' ) ) { return $fail( 'storage', 'The schedule could not be saved.' ); }
    return event_get( $id );
}
// Tombstones close the race between a schedule save and WordPress trash/delete.
function event_pre_remove( $check, $post ) {
    if ( null !== $check || 'rivage_event' !== $post->post_type ) { return $check; }
    $result = event_change( $post->ID, 'archive', array(), null, true );
    if ( is_wp_error( $result ) ) { return false; }
    return null;
}
add_filter( 'pre_trash_post', __NAMESPACE__ . '\\event_pre_remove', 10, 2 );
add_filter( 'pre_delete_post', __NAMESPACE__ . '\\event_pre_remove', 10, 2 );
add_action( 'untrashed_post', static function ( $id ) { if ( 'rivage_event' === get_post_type( $id ) ) { event_change( $id, 'restore', array(), null, true ); } } );
