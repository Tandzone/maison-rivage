<?php
require_once __DIR__ . '/booking-helpers.php';
$initial_user = get_current_user_id(); $saved_post = $_POST; $error = null;
$type = get_page_by_path( 'rooms/garden-room' );
$original = get_post_meta( $type->ID, '_rivage_gallery', true );
try {
    foreach ( array( 'garden-room', 'ocean-balcony-room', 'rivage-suite' ) as $slug ) {
        $page = get_page_by_path( 'rooms/' . $slug ); $ids = \MaisonRivage\gallery_images( $page->ID );
        rivage_booking_assert( 3 === count( $ids ), 'Expected three seeded photographs: ' . $slug );
        foreach ( $ids as $id ) { rivage_booking_assert( wp_attachment_is_image( $id ) && get_post_meta( $id, '_wp_attachment_image_alt', true ), 'Missing gallery image/alt.' ); }
        rivage_booking_assert( (int) get_post_thumbnail_id( $page->ID ) === $ids[0], 'Catalogue photograph not updated.' );
        rivage_booking_assert( str_contains( $page->post_content, '[rivage_room_gallery]' ), 'Gallery missing from category page.' );
    }
    $ids = \MaisonRivage\gallery_images( $type->ID );
    rivage_booking_assert( array( $ids[0] ) === \MaisonRivage\gallery_ids( array( $ids[0], $ids[0], $type->ID, 0 ) ), 'Gallery sanitization failed.' );
    wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
    $_POST = array( 'rivage_gallery_ids' => implode( ',', array_reverse( $ids ) ), 'rivage_gallery_nonce' => 'forged' );
    do_action( 'save_post_page', $type->ID, $type );
    rivage_booking_assert( $ids === \MaisonRivage\gallery_images( $type->ID ), 'Forged nonce changed gallery.' );
    $_POST['rivage_gallery_nonce'] = wp_create_nonce( 'rivage_gallery_' . $type->ID );
    do_action( 'save_post_page', $type->ID, $type );
    rivage_booking_assert( array_reverse( $ids ) === \MaisonRivage\gallery_images( $type->ID ), 'Authorized reorder failed.' );
    wp_set_current_user( 0 ); $_POST['rivage_gallery_ids'] = '';
    do_action( 'save_post_page', $type->ID, $type );
    rivage_booking_assert( array_reverse( $ids ) === \MaisonRivage\gallery_images( $type->ID ), 'Anonymous save changed gallery.' );
} catch ( Throwable $e ) { $error = $e->getMessage(); }
finally { update_post_meta( $type->ID, '_rivage_gallery', $original ); wp_set_current_user( $initial_user ); $_POST = $saved_post; }
if ( $error ) { WP_CLI::error( $error ); }
WP_CLI::success( 'Gallery checks passed: three categories, nine images with alt text, catalogue images, nonce/capability protection and saved ordering.' );
