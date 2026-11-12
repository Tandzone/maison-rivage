<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;
function event_schedule_url( $id ) { return admin_url( 'admin.php?page=rivage-event-schedule&event=' . absint( $id ) ); }
add_action( 'admin_menu', static function () { add_submenu_page( 'maison-rivage', 'Event Schedule', 'Event Schedule', 'manage_options', 'rivage-event-schedule', __NAMESPACE__ . '\\event_schedule_screen' ); } );
function event_local_value( $utc ) { return $utc ? ( new \DateTimeImmutable( $utc, new \DateTimeZone( 'UTC' ) ) )->setTimezone( new \DateTimeZone( 'Indian/Antananarivo' ) )->format( 'Y-m-d\TH:i' ) : ''; }
add_action( 'add_meta_boxes_rivage_event', static function () {
    add_meta_box( 'rivage-event-schedule', 'Maison Rivage — Schedule & Venue', static function ( $post ) {
        $row = event_get( $post->ID );
        echo '<p>Schedule status: <strong>' . esc_html( $row ? ucfirst( $row->status ) : 'Not scheduled' ) . '</strong></p><p>Publishing edits the public story. Scheduling is a separate, availability-checked operation.</p><a class="button" href="' . esc_url( event_schedule_url( $post->ID ) ) . '">Manage Schedule & Venue</a>';
    }, 'rivage_event', 'side' );
} );
add_filter( 'manage_rivage_event_posts_columns', static function ( $columns ) { $columns['rivage_schedule'] = 'Schedule & Venue'; return $columns; } );
add_action( 'manage_rivage_event_posts_custom_column', static function ( $column, $id ) {
    if ( 'rivage_schedule' !== $column ) { return; }
    $row = event_get( $id );
    echo '<a href="' . esc_url( event_schedule_url( $id ) ) . '">' . esc_html( $row ? ucfirst( $row->status ) : 'Not scheduled' ) . '</a>';
    if ( $row && $row->starts_at ) { echo '<br>' . esc_html( str_replace( 'T', ' ', event_local_value( $row->starts_at ) ) ) . '<br>' . esc_html( 'suite' === $row->venue ? get_the_title( $row->room_id ) : 'Hall' ); }
}, 10, 2 );
function event_schedule_screen() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $id = isset( $_GET['event'] ) && is_scalar( $_GET['event'] ) ? absint( $_GET['event'] ) : 0;
    $notice = get_transient( 'rivage_event_notice_' . get_current_user_id() );
    if ( $notice ) { delete_transient( 'rivage_event_notice_' . get_current_user_id() ); }
    echo '<div class="wrap"><h1>Event Schedule & Venue</h1>';
    if ( $notice ) { echo '<div class="notice ' . ( $notice['error'] ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $notice['text'] ) . '</p></div>'; }
    $post = get_post( $id );
    if ( ! $post || 'rivage_event' !== $post->post_type ) {
        echo '<p>Create or select an event in <a href="' . esc_url( admin_url( 'edit.php?post_type=rivage_event' ) ) . '">Events</a>, then choose its Schedule & Venue link.</p></div>'; return;
    }
    $row = event_get( $id );
    echo '<h2>' . esc_html( $post->post_title ) . '</h2><p><a href="' . esc_url( get_edit_post_link( $id ) ) . '">Edit story and image</a> · <a href="' . esc_url( get_permalink( $id ) ) . '">View event</a></p><p>Schedule status: <strong>' . esc_html( $row ? ucfirst( $row->status ) : 'Not scheduled' ) . '</strong> · Editorial visibility: ' . esc_html( $post->post_status ) . '</p>';
    echo '<p>All times are Madagascar time (UTC+3). Suite events block the selected suite from one hour before the start until one hour after the end. Hall events do not block rooms.</p><p>Saving checks confirmed stays and other suite events. A conflicting edit preserves the previous schedule. Unpublishing the story does not cancel its operational schedule; use Cancel Event to release inventory.</p>';
    if ( 'trash' === $post->post_status || ( $row && 'archived' === $row->status ) ) { echo '<p>Restore the event before scheduling it.</p></div>'; return; }
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rivage_event_schedule"><input type="hidden" name="event_id" value="' . esc_attr( $id ) . '"><input type="hidden" name="revision" value="' . esc_attr( $row ? $row->revision : 0 ) . '">';
    wp_nonce_field( 'rivage_event_schedule_' . $id );
    echo '<table class="form-table"><tr><th><label for="event-start">Starts</label></th><td><input id="event-start" type="datetime-local" name="start" value="' . esc_attr( $row ? event_local_value( $row->starts_at ) : '' ) . '"></td></tr><tr><th><label for="event-end">Ends</label></th><td><input id="event-end" type="datetime-local" name="end" value="' . esc_attr( $row ? event_local_value( $row->ends_at ) : '' ) . '"><p class="description">Maximum duration: 24 hours. Past dates are allowed for editorial archives.</p></td></tr><tr><th><label for="event-venue">Venue</label></th><td><select id="event-venue" name="venue"><option value="hall">Hall</option><option value="suite" ' . selected( $row ? $row->venue : '', 'suite', false ) . '>Rivage Suite</option></select></td></tr><tr><th><label for="event-room">Suite</label></th><td><select id="event-room" name="room_id"><option value="0">Select a suite (ignored for Hall)</option>';
    $rooms = get_posts( array( 'post_type' => 'rivage_room', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    foreach ( $rooms as $room ) { if ( event_room_valid( $room->ID ) ) { echo '<option value="' . esc_attr( $room->ID ) . '" ' . selected( $row ? $row->room_id : 0, $room->ID, false ) . '>' . esc_html( $room->post_title ) . '</option>'; } }
    echo '</select></td></tr></table><p><button class="button button-primary" name="transition" value="schedule">Save Event Schedule</button> <button class="button" name="transition" value="cancel">Cancel Event & Release Suite</button></p></form></div>';
}
add_action( 'admin_post_rivage_event_schedule', static function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
    $value = static function ( $key ) { return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ) : ''; };
    $id = absint( $value( 'event_id' ) );
    check_admin_referer( 'rivage_event_schedule_' . $id );
    $revision = $value( 'revision' );
    $result = ctype_digit( $revision ) ? event_change( $id, $value( 'transition' ), array( 'start' => $value( 'start' ), 'end' => $value( 'end' ), 'venue' => $value( 'venue' ), 'room_id' => $value( 'room_id' ) ), (int) $revision ) : booking_error( 'stale', 'Reload the schedule before saving.' );
    set_transient( 'rivage_event_notice_' . get_current_user_id(), array( 'error' => is_wp_error( $result ), 'text' => is_wp_error( $result ) ? $result->get_error_message() : 'Event ' . $result->status . '. Room availability updated.' ), 120 );
    wp_safe_redirect( event_schedule_url( $id ) ); exit;
} );

add_action( 'rivage_overview_events', static function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT e.*,p.post_title,p.post_status FROM ' . booking_table( 'events' ) . " e INNER JOIN {$wpdb->posts} p ON p.ID=e.event_id WHERE e.status='scheduled' AND e.ends_at>=%s ORDER BY e.starts_at LIMIT 5", gmdate( 'Y-m-d H:i:s' ) ) );
    echo '<h2>Upcoming Events</h2><p><a href="' . esc_url( admin_url( 'edit.php?post_type=rivage_event' ) ) . '">Manage events and stories</a></p>';
    if ( ! $rows ) { echo '<p>No upcoming events scheduled.</p>'; return; }
    echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>Event</th><th>Time (Madagascar)</th><th>Venue</th><th>Story visibility</th></tr></thead><tbody>';
    foreach ( $rows as $row ) { echo '<tr><td><a href="' . esc_url( event_schedule_url( $row->event_id ) ) . '">' . esc_html( $row->post_title ) . '</a></td><td>' . esc_html( str_replace( 'T', ' ', event_local_value( $row->starts_at ) ) ) . '</td><td>' . esc_html( $row->room_id ? get_the_title( $row->room_id ) : 'Hall' ) . '</td><td>' . esc_html( $row->post_status ) . '</td></tr>'; }
    echo '</tbody></table>';
} );
