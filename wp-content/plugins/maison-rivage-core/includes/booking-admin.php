<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;
function booking_status_label( $status ) {
    return array( 'unverified' => 'Awaiting email verification', 'pending' => 'Pending hotel review', 'confirmed' => 'Confirmed', 'declined' => 'Declined', 'cancelled' => 'Cancelled' )[ $status ] ?? $status;
}
add_action( 'admin_menu', static function () {
    add_submenu_page( 'maison-rivage', 'Bookings', 'Bookings', 'manage_options', 'rivage-bookings', __NAMESPACE__ . '\\bookings_screen' );
    add_submenu_page( 'maison-rivage', 'Availability', 'Availability', 'manage_options', 'rivage-availability', __NAMESPACE__ . '\\availability_screen' );
    add_submenu_page( 'maison-rivage', 'Notifications', 'Notifications', 'manage_options', 'rivage-notifications', __NAMESPACE__ . '\\notifications_screen' );
} );
function booking_admin_action() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
    $id = isset( $_POST['booking_id'] ) && is_scalar( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
    check_admin_referer( 'rivage_manage_booking_' . $id );
    $action = isset( $_POST['transition'] ) && is_string( $_POST['transition'] ) ? sanitize_key( $_POST['transition'] ) : '';
    $room = isset( $_POST['room_id'] ) && is_scalar( $_POST['room_id'] ) ? absint( $_POST['room_id'] ) : 0;
    $result = booking_transition( $id, $action, $room );
    set_transient( 'rivage_booking_notice_' . get_current_user_id(), is_wp_error( $result ) ? $result->get_error_message() : 'Request updated: ' . booking_status_label( $result->status ) . '. Email queued.', 90 );
    if ( ! is_wp_error( $result ) ) { dispatch_booking_mail(); }
    wp_safe_redirect( admin_url( 'admin.php?page=rivage-bookings&booking=' . $id ) ); exit;
}
add_action( 'admin_post_rivage_manage_booking', __NAMESPACE__ . '\\booking_admin_action' );
function bookings_screen() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    global $wpdb;
    $id = isset( $_GET['booking'] ) && is_scalar( $_GET['booking'] ) ? absint( $_GET['booking'] ) : 0;
    $notice = get_transient( 'rivage_booking_notice_' . get_current_user_id() );
    if ( $notice ) { delete_transient( 'rivage_booking_notice_' . get_current_user_id() ); }
    echo '<div class="wrap"><h1>Bookings</h1>';
    if ( $notice ) { echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>'; }
    if ( $id ) {
        $row = booking_get( $id );
        if ( ! $row ) { echo '<p>Request not found.</p></div>'; return; }
        echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=rivage-bookings' ) ) . '">← All requests</a></p>';
        echo '<h2>Request ' . esc_html( $row->reference ) . '</h2><table class="widefat striped" style="max-width:900px"><tbody>';
        foreach ( array( 'Status' => booking_status_label( $row->status ), 'Guest' => $row->guest_name, 'Email' => $row->email,
            'Category' => get_the_title( $row->type_id ), 'Dates' => $row->check_in . ' → ' . $row->check_out,
            'Party' => $row->adults . ' adults, ' . $row->children . ' children', 'Quoted nightly rate' => format_rate( (int) $row->rate_cents ),
            'Quoted demo total' => format_rate( (int) $row->total_cents ), 'Assigned room' => $row->room_id ? get_the_title( $row->room_id ) : 'Not assigned', 'Message' => $row->message ) as $key => $value ) {
            echo '<tr><th style="width:220px">' . esc_html( $key ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
        }
        echo '</tbody></table>';
        $other = $wpdb->get_results( $wpdb->prepare( 'SELECT id,reference FROM ' . booking_table() . " WHERE type_id=%d AND status='pending' AND id<>%d AND starts_at<%s AND ends_at>%s", $row->type_id, $id, $row->ends_at, $row->starts_at ) );
        if ( $other ) {
            echo '<div class="notice notice-warning inline"><p>Other pending requests overlap this category. They do not block inventory and are not automatically cancelled: ';
            foreach ( $other as $request ) { echo '<a href="' . esc_url( admin_url( 'admin.php?page=rivage-bookings&booking=' . $request->id ) ) . '">' . esc_html( $request->reference ) . '</a> '; }
            echo '</p></div>';
        }
        if ( in_array( $row->status, array( 'pending', 'confirmed' ), true ) ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:24px">';
            wp_nonce_field( 'rivage_manage_booking_' . $id );
            echo '<input type="hidden" name="action" value="rivage_manage_booking"><input type="hidden" name="booking_id" value="' . (int) $id . '">';
            if ( 'pending' === $row->status ) {
                $stay = (array) $row;
                $free = available_rooms( $row->type_id, $stay );
                echo '<label for="rivage-assign-room"><strong>Assign an available room</strong></label> <select name="room_id" id="rivage-assign-room"><option value="">Choose a room</option>';
                foreach ( $free as $room ) { echo '<option value="' . (int) $room->ID . '">' . esc_html( $room->post_title ) . '</option>'; }
                echo '</select> <button class="button button-primary" name="transition" value="confirm">Confirm Booking</button> <button class="button" name="transition" value="decline">Decline Request</button>';
                if ( ! $free ) { echo '<p class="notice notice-warning inline">No room in this category is currently available for this party and stay. Confirmation will be blocked.</p>'; }
                echo '<p class="description">Availability and occupancy conflicts are checked again when confirming. The quoted price is preserved.</p>';
            }
            echo '<p><button class="button" name="transition" value="cancel">Cancel Request / Booking</button></p></form>';
        }
        echo '</div>'; return;
    }
    $status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
    if ( ! in_array( $status, array( 'unverified', 'pending', 'confirmed', 'declined', 'cancelled' ), true ) ) { $status = ''; }
    $page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $where = $status ? $wpdb->prepare( ' WHERE status=%s', $status ) : '';
    $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . booking_table() . $where . ' ORDER BY id DESC LIMIT 20 OFFSET %d', ( $page - 1 ) * 20 ) );
    echo '<form method="get"><input type="hidden" name="page" value="rivage-bookings"><label for="booking-status">Status </label><select id="booking-status" name="status"><option value="">All statuses</option>';
    foreach ( array( 'unverified', 'pending', 'confirmed', 'declined', 'cancelled' ) as $key ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( booking_status_label( $key ) ) . '</option>'; }
    echo '</select> <button class="button">Filter</button></form><p>Only confirmed bookings block rooms. Pending requests require a personal review.</p><table class="widefat striped"><thead><tr><th>Reference</th><th>Guest</th><th>Category</th><th>Stay</th><th>Status</th><th>Attention</th></tr></thead><tbody>';
    foreach ( $rows as $row ) {
        $warnings = '';
        if ( 'pending' === $row->status ) {
            $free = available_rooms( $row->type_id, (array) $row );
            $warnings = count( $free ) . ' room(s) available';
            $competing = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . booking_table() . " WHERE type_id=%d AND id<>%d AND status='pending' AND starts_at<%s AND ends_at>%s", $row->type_id, $row->id, $row->ends_at, $row->starts_at ) );
            if ( $competing ) { $warnings .= ' · ' . $competing . ' overlapping pending request(s)'; }
        }
        echo '<tr><td><a href="' . esc_url( admin_url( 'admin.php?page=rivage-bookings&booking=' . $row->id ) ) . '">' . esc_html( $row->reference ) . '</a></td><td>' . esc_html( $row->guest_name ) . '</td><td>' . esc_html( get_the_title( $row->type_id ) ) . '</td><td>' . esc_html( $row->check_in . ' → ' . $row->check_out ) . '</td><td>' . esc_html( booking_status_label( $row->status ) ) . '</td><td>' . esc_html( $warnings ) . '</td></tr>';
    }
    if ( ! $rows ) { echo '<tr><td colspan="6">No requests match this filter.</td></tr>'; }
    echo '</tbody></table><p>';
    if ( $page > 1 ) { echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'rivage-bookings', 'status' => $status, 'paged' => $page - 1 ), admin_url( 'admin.php' ) ) ) . '">Previous</a> '; }
    if ( count( $rows ) === 20 ) { echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'rivage-bookings', 'status' => $status, 'paged' => $page + 1 ), admin_url( 'admin.php' ) ) ) . '">Next</a>'; }
    echo '</p></div>';
}
function availability_screen() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    global $wpdb;
    $start = new \DateTimeImmutable( 'today', new \DateTimeZone( 'Indian/Antananarivo' ) );
    $value = isset( $_GET['from'] ) && is_string( $_GET['from'] ) ? sanitize_text_field( $_GET['from'] ) : '';
    $candidate = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $start->getTimezone() );
    if ( $candidate && $candidate->format( 'Y-m-d' ) === $value ) { $start = $candidate; }
    echo '<div class="wrap"><h1>Room Availability</h1><p>14-night view, Madagascar time. Confirmed stays and room occupations block inventory; pending requests do not.</p><form method="get"><input type="hidden" name="page" value="rivage-availability"><label>From <input type="date" name="from" value="' . esc_attr( $start->format( 'Y-m-d' ) ) . '"></label> <button class="button">Show dates</button></form><div style="overflow:auto;margin-top:20px"><table class="widefat striped"><thead><tr><th>Room</th>';
    for ( $day = 0; $day < 14; $day++ ) { echo '<th>' . esc_html( $start->modify( '+' . $day . ' days' )->format( 'd M' ) ) . '</th>'; }
    echo '</tr></thead><tbody>';
    foreach ( get_posts( array( 'post_type' => 'rivage_room', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $room ) {
        echo '<tr><th>' . esc_html( $room->post_title ) . '</th>';
        for ( $day = 0; $day < 14; $day++ ) {
            $date = $start->modify( '+' . $day . ' days' );
            $utc = new \DateTimeZone( 'UTC' );
            $occupied = occupancy_conflict( $room->ID, $date->setTime( 14, 0 )->setTimezone( $utc )->format( 'Y-m-d H:i:s' ), $date->modify( '+1 day' )->setTime( 11, 0 )->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) );
            echo '<td>';
            if ( $occupied && $occupied->booking_id ) { echo '<a href="' . esc_url( admin_url( 'admin.php?page=rivage-bookings&booking=' . $occupied->booking_id ) ) . '">Booked</a>'; }
            elseif ( $occupied && 'event' === $occupied->source_type ) { echo '<a href="' . esc_url( event_schedule_url( $occupied->source_id ) ) . '">Event</a>'; }
            elseif ( $occupied ) { echo 'Occupied'; }
            else { echo 'active' === get_post_meta( $room->ID, '_rivage_status', true ) ? 'Open' : 'Out of service'; }
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div></div>';
}
function notifications_screen() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    global $wpdb;
    echo '<div class="wrap"><h1>Notifications</h1><p>Email dispatch status. “Sent” means accepted by the configured mail transport, not guaranteed inbox delivery.</p>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'; wp_nonce_field( 'rivage_retry_mail' );
    echo '<input type="hidden" name="action" value="rivage_retry_mail"><button class="button">Retry Pending / Failed Emails</button></form><table class="widefat striped" style="margin-top:20px"><thead><tr><th>Request</th><th>Subject</th><th>Recipient</th><th>Status</th><th>Attempts</th></tr></thead><tbody>';
    foreach ( $wpdb->get_results( 'SELECT * FROM ' . booking_table( 'mail' ) . ' ORDER BY id DESC LIMIT 100' ) as $mail ) {
        echo '<tr><td>' . ( $mail->booking_id ? '<a href="' . esc_url( admin_url( 'admin.php?page=rivage-bookings&booking=' . $mail->booking_id ) ) . '">#' . (int) $mail->booking_id . '</a>' : 'Contact message' ) . '</td><td>' . esc_html( $mail->subject ) . '</td><td>' . esc_html( $mail->recipient ) . '</td><td>' . esc_html( $mail->status ) . '</td><td>' . (int) $mail->attempts . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
add_action( 'admin_post_rivage_retry_mail', static function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
    check_admin_referer( 'rivage_retry_mail' ); global $wpdb;
    $wpdb->query( 'UPDATE ' . booking_table( 'mail' ) . " SET status='queued',attempts=0,next_attempt=0 WHERE status IN ('queued','failed')" );
    dispatch_booking_mail(); wp_safe_redirect( admin_url( 'admin.php?page=rivage-notifications' ) ); exit;
} );
add_action( 'admin_notices', static function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; } global $wpdb;
    $pending = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . booking_table() . " WHERE status='pending'" );
    $failed = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . booking_table( 'mail' ) . " WHERE status='failed'" );
    if ( $pending ) { echo '<div class="notice notice-info"><p><a href="' . esc_url( admin_url( 'admin.php?page=rivage-bookings&status=pending' ) ) . '">' . $pending . ' verified request(s) awaiting review.</a></p></div>'; }
    if ( $failed ) { echo '<div class="notice notice-error"><p><a href="' . esc_url( admin_url( 'admin.php?page=rivage-notifications' ) ) . '">' . $failed . ' email(s) need attention.</a> Booking records remain saved.</p></div>'; }
} );
