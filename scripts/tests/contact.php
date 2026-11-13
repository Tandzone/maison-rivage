<?php
require_once __DIR__ . '/booking-helpers.php';
global $wpdb;
$ids = array(); $error = null; $filter = null;
try {
    $input = array( 'guest_name' => 'Contact QA', 'email' => 'contact-qa@example.test', 'message' => 'Disposable local contact test.', 'demo_ack' => '1' );
    rivage_booking_assert( is_wp_error( \MaisonRivage\contact_submit( $input, 'invalid' ) ), 'Unsigned contact accepted.' );
    foreach ( array( array( 'email' => "guest@example.test\r\nBcc: other@example.test" ), array( 'message' => 'short' ), array( 'demo_ack' => '' ), array( 'guest_name' => array() ) ) as $bad ) {
        rivage_booking_assert( is_wp_error( \MaisonRivage\contact_submit( array_replace( $input, $bad ), \MaisonRivage\booking_form_key() ) ), 'Invalid contact input accepted.' );
    }
    $key = \MaisonRivage\booking_form_key();
    $id = \MaisonRivage\contact_submit( $input, $key );
    rivage_booking_assert( is_int( $id ), 'Contact queue failed.' ); $ids[] = $id;
    rivage_booking_assert( $id === \MaisonRivage\contact_submit( $input, $key ), 'Contact retry duplicated mail.' );
    $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE id=%d', $id ) );
    rivage_booking_assert( 0 === (int) $row->booking_id && get_option( 'admin_email' ) === $row->recipient && str_contains( $row->body, $input['email'] ), 'Contact recipient/body wrong.' );
    $filter = static function ( $result, $args ) { return 'Maison Rivage — contact demonstration' === $args['subject'] ? false : $result; };
    add_filter( 'pre_wp_mail', $filter, 10, 2 ); \MaisonRivage\dispatch_booking_mail(); remove_filter( 'pre_wp_mail', $filter, 10 ); $filter = null;
    $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE id=%d', $id ) );
    rivage_booking_assert( 'queued' === $row->status && 1 === (int) $row->attempts && '' !== $row->body, 'Transport failure lost contact message.' );
    $wpdb->update( \MaisonRivage\booking_table( 'mail' ), array( 'next_attempt' => 0 ), array( 'id' => $id ) );
    $filter = static function ( $result, $args ) { return 'Maison Rivage — contact demonstration' === $args['subject'] ? true : $result; };
    add_filter( 'pre_wp_mail', $filter, 10, 2 ); \MaisonRivage\dispatch_booking_mail(); remove_filter( 'pre_wp_mail', $filter, 10 ); $filter = null;
    $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE id=%d', $id ) );
    rivage_booking_assert( 'sent' === $row->status && '' === $row->body, 'Successful contact did not clear private body.' );
    rivage_booking_assert( $id === \MaisonRivage\contact_submit( $input, $key ), 'Retry after delivery duplicated contact.' );
    $wpdb->update( \MaisonRivage\booking_table( 'mail' ), array( 'created_at' => time() - 2 * DAY_IN_SECONDS ), array( 'id' => $id ) );
    do_action( 'rivage_mail_tick' );
    rivage_booking_assert( ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . \MaisonRivage\booking_table( 'mail' ) . ' WHERE id=%d', $id ) ), 'Contact retention cleanup failed.' );
} catch ( Throwable $exception ) { $error = $exception->getMessage(); }
finally { if ( $filter ) { remove_filter( 'pre_wp_mail', $filter, 10 ); } foreach ( $ids as $id ) { $wpdb->delete( \MaisonRivage\booking_table( 'mail' ), array( 'id' => $id ) ); } }
if ( $error ) { WP_CLI::error( $error ); }
WP_CLI::success( 'Contact tests passed: input validation, injection rejection, idempotency, admin recipient, transport failure/retry, private body clearing and retention.' );
