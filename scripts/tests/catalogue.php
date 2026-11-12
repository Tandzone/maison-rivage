<?php
/** Integration checks against local WordPress; all temporary changes are restored. */
if ( 'local' !== wp_get_environment_type() ) { WP_CLI::error( 'Run catalogue checks only in the local environment.' ); }
function rivage_expect( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
$initial_user = get_current_user_id();
$initial_post = $_POST;
$page = get_page_by_path( 'rooms/ocean-balcony-room' );
if ( ! $page ) { WP_CLI::error( 'Run ./bin/dev setup to create the demo categories first.' ); }
$fields = array( 'capacity', 'max_adults', 'max_children', 'outdoor', 'rate_cents' );
$backup = array();
foreach ( $fields as $key ) { $backup[ $key ] = get_post_meta( $page->ID, '_rivage_' . $key, true ); }
$test_rooms = get_posts( array( 'post_type' => 'rivage_room', 'post_status' => 'publish', 'numberposts' => 1 ) );
if ( ! $test_rooms ) { WP_CLI::error( 'Run ./bin/dev setup to create the demo inventory first.' ); }
$room = $test_rooms[0];
$room_backup = array( 'type_id' => get_post_meta( $room->ID, '_rivage_type_id', true ), 'status' => get_post_meta( $room->ID, '_rivage_status', true ) );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
try {
    rivage_expect( 9500 === \MaisonRivage\parse_rate( '95' ), 'Whole-euro parsing failed.' );
    rivage_expect( 13505 === \MaisonRivage\parse_rate( '135,05' ), 'Decimal comma parsing failed.' );
    rivage_expect( null === \MaisonRivage\parse_rate( '' ), 'Blank rate must hide pricing.' );
    foreach ( array( '-1', '0', '12.345', '1e3', '999999999', '<script>' ) as $invalid ) {
        rivage_expect( is_wp_error( \MaisonRivage\parse_rate( $invalid ) ), 'Malformed price accepted.' );
    }
    $valid = array( 'capacity' => '2', 'max_adults' => '2', 'max_children' => '2', 'outdoor' => 'Ocean-view balcony', 'rate' => '135.01' );
    rivage_expect( ! is_wp_error( \MaisonRivage\validate_type_fields( $valid ) ), 'Valid category rejected.' );
    foreach ( array( array( 'capacity' => '1' ), array( 'max_adults' => '0' ), array( 'rate' => array() ), array( 'outdoor' => array() ) ) as $invalid ) {
        rivage_expect( is_wp_error( \MaisonRivage\validate_type_fields( array_replace( $valid, $invalid ) ) ), 'Invalid category fields accepted.' );
    }
    rivage_expect( is_wp_error( \MaisonRivage\validate_room_fields( array( 'type_id' => 0, 'status' => 'active' ) ) ), 'Missing category accepted.' );
    rivage_expect( is_wp_error( \MaisonRivage\validate_room_fields( array( 'type_id' => $page->ID, 'status' => 'booked' ) ) ), 'Operational status confused with booking status.' );
    rivage_expect( ! get_post_type_object( 'rivage_room' )->publicly_queryable, 'Physical inventory is publicly queryable.' );
    rivage_expect( 404 === rest_do_request( '/wp/v2/rivage_room' )->get_status(), 'Inventory REST route exposed.' );

    wp_set_current_user( $admin->ID );
    $_POST = array( 'rivage_type_nonce' => wp_create_nonce( 'rivage_type_' . $page->ID ), 'rivage_type' => $valid );
    \MaisonRivage\save_details( $page->ID, $page );
    rivage_expect( 13501 === (int) get_post_meta( $page->ID, '_rivage_rate_cents', true ), 'Valid admin save failed.' );
    $html = do_shortcode( '[rivage_room_facts type="ocean-balcony-room"]' );
    rivage_expect( str_contains( $html, '135.01 €' ), 'Public facts do not reflect the saved rate.' );

    $_POST = array( 'rivage_room_nonce' => wp_create_nonce( 'rivage_room_' . $room->ID ),
        'rivage_room' => array( 'type_id' => (string) $page->ID, 'status' => 'out_of_service' ) );
    \MaisonRivage\save_details( $room->ID, $room );
    rivage_expect( 'out_of_service' === get_post_meta( $room->ID, '_rivage_status', true ), 'Room operational save failed.' );
    $_POST['rivage_room']['type_id'] = '0';
    $_POST['rivage_room']['status'] = 'active';
    \MaisonRivage\save_details( $room->ID, $room );
    rivage_expect( 'out_of_service' === get_post_meta( $room->ID, '_rivage_status', true ), 'Invalid room relation partially saved.' );

    $_POST = array( 'rivage_type_nonce' => wp_create_nonce( 'rivage_type_' . $page->ID ), 'rivage_type' => $valid );

    $_POST['rivage_type']['rate'] = '-10';
    $_POST['rivage_type']['outdoor'] = 'Must not be saved';
    \MaisonRivage\save_details( $page->ID, $page );
    rivage_expect( 13501 === (int) get_post_meta( $page->ID, '_rivage_rate_cents', true ) && 'Ocean-view balcony' === get_post_meta( $page->ID, '_rivage_outdoor', true ), 'Invalid save partially changed hotel fields.' );
    $_POST['rivage_type'] = array_replace( $valid, array( 'rate' => '999' ) );
    $_POST['rivage_type_nonce'] = 'invalid';
    \MaisonRivage\save_details( $page->ID, $page );
    rivage_expect( 13501 === (int) get_post_meta( $page->ID, '_rivage_rate_cents', true ), 'Invalid nonce was accepted.' );
    $_POST['rivage_type_nonce'] = wp_create_nonce( 'rivage_type_' . $page->ID );
    wp_set_current_user( 0 );
    \MaisonRivage\save_details( $page->ID, $page );
    rivage_expect( 13501 === (int) get_post_meta( $page->ID, '_rivage_rate_cents', true ), 'Unauthenticated metadata update accepted.' );
    update_post_meta( $page->ID, '_rivage_outdoor', '<script>alert(1)</script>' );
    rivage_expect( ! str_contains( do_shortcode( '[rivage_room_facts type="ocean-balcony-room"]' ), '<script>' ), 'Public facts are not escaped.' );
    WP_CLI::success( 'Catalogue checks passed: validation, admin save, live rendering, nonce/capability checks and private inventory.' );
} finally {
    foreach ( $backup as $key => $value ) { update_post_meta( $page->ID, '_rivage_' . $key, $value ); }
    foreach ( $room_backup as $key => $value ) { update_post_meta( $room->ID, '_rivage_' . $key, $value ); }
    delete_transient( 'rivage_save_error_' . $admin->ID . '_' . $page->ID );
    delete_transient( 'rivage_save_error_' . $admin->ID . '_' . $room->ID );
    $_POST = $initial_post;
    wp_set_current_user( $initial_user );
}
