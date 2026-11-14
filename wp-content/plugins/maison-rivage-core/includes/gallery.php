<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;
function gallery_ids( $raw ) {
    $ids = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
    return array_values( array_unique( array_filter( array_map( 'absint', array_slice( $ids, 0, 12 ) ), static function ( $id ) {
        return 'attachment' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) && wp_attachment_is_image( $id );
    } ) ) );
}
function gallery_images( $type ) {
    return gallery_ids( get_post_meta( $type, '_rivage_gallery', true ) ?: array() );
}
add_action( 'add_meta_boxes_page', static function ( $post ) {
    if ( ! is_type_editor( $post ) || ! current_user_can( 'manage_options' ) ) { return; }
    add_meta_box( 'rivage-gallery', 'Maison Rivage — Room Gallery', static function ( $post ) {
        wp_nonce_field( 'rivage_gallery_' . $post->ID, 'rivage_gallery_nonce' );
        $items = array();
        foreach ( gallery_images( $post->ID ) as $id ) { $items[] = array( 'id' => $id, 'url' => wp_get_attachment_image_url( $id, 'thumbnail' ), 'title' => get_the_title( $id ) ); }
        echo '<p>Choose up to 12 images. Order them below. Edit alternative text and captions in the Media Library. The Featured image controls catalogue cards separately. The second gallery photo also illustrates the outdoor section.</p><div class="rivage-gallery-editor" data-items="' . esc_attr( wp_json_encode( $items ) ) . '"><input type="hidden" name="rivage_gallery_ids" value="' . esc_attr( implode( ',', gallery_images( $post->ID ) ) ) . '"><p><button type="button" class="button rivage-gallery-choose">Choose Gallery Photos</button></p><ol class="rivage-gallery-admin-list"></ol></div>';
    }, 'page', 'normal' );
} );
add_action( 'admin_enqueue_scripts', static function () {
    $screen = get_current_screen();
    if ( ! $screen || 'post' !== $screen->base || 'page' !== $screen->post_type || ! current_user_can( 'manage_options' ) ) { return; }
    wp_enqueue_media();
    wp_enqueue_script( 'rivage-gallery-admin', plugins_url( 'assets/gallery-admin.js', dirname( __DIR__ ) . '/maison-rivage-core.php' ), array( 'media-views' ), (string) filemtime( dirname( __DIR__ ) . '/assets/gallery-admin.js' ), true );
} );
add_action( 'save_post_page', static function ( $id, $post ) {
    if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ! is_type_editor( $post ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) ) { return; }
    if ( ! isset( $_POST['rivage_gallery_nonce'], $_POST['rivage_gallery_ids'] ) || ! is_string( $_POST['rivage_gallery_nonce'] ) || ! is_string( $_POST['rivage_gallery_ids'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rivage_gallery_nonce'] ) ), 'rivage_gallery_' . $id ) ) { return; }
    update_post_meta( $id, '_rivage_gallery', gallery_ids( sanitize_text_field( wp_unslash( $_POST['rivage_gallery_ids'] ) ) ) );
}, 10, 2 );
add_shortcode( 'rivage_room_gallery', static function () {
    $ids = gallery_images( get_queried_object_id() );
    if ( ! $ids ) { return '<p>Room photographs will be available soon.</p>'; }
    $first = $ids[0];
    $caption = wp_strip_all_tags( wp_get_attachment_caption( $first ) ?: get_the_title( $first ) );
    $html = '<section class="rivage-gallery" aria-label="Room photo gallery"><a class="rivage-gallery-main" href="' . esc_url( wp_get_attachment_image_url( $first, 'full' ) ) . '" aria-label="Open room photo viewer">' . wp_get_attachment_image( $first, 'large', false, array( 'loading' => 'eager' ) ) . '<span class="rivage-gallery-open">View photos <span aria-hidden="true">↗</span></span></a><p class="rivage-gallery-caption" aria-live="polite">' . esc_html( $caption ) . ' · 1 / ' . count( $ids ) . '</p><div class="rivage-gallery-thumbs" aria-label="Choose a photograph">';
    foreach ( $ids as $i => $id ) {
        $alt = get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: get_the_title( $id );
        $label = wp_strip_all_tags( wp_get_attachment_caption( $id ) ?: get_the_title( $id ) );
        $html .= '<a href="' . esc_url( wp_get_attachment_image_url( $id, 'full' ) ) . '" data-gallery-photo data-alt="' . esc_attr( $alt ) . '" data-caption="' . esc_attr( $label ) . '" data-src="' . esc_url( wp_get_attachment_image_url( $id, 'large' ) ) . '" data-srcset="' . esc_attr( wp_get_attachment_image_srcset( $id, 'large' ) ?: '' ) . '" aria-label="' . esc_attr( 'Show photo ' . ( $i + 1 ) . ': ' . $label ) . '"' . ( 0 === $i ? ' aria-current="true"' : '' ) . '>' . wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ) . '</a>';
    }
    return $html . '</div></section>';
} );
add_shortcode( 'rivage_room_outdoor', static function () {
    $ids = gallery_images( get_queried_object_id() );
    return isset( $ids[1] ) ? '<figure class="rivage-outdoor-photo">' . wp_get_attachment_image( $ids[1], 'large', false, array( 'loading' => 'lazy' ) ) . '</figure>' : '';
} );
add_action( 'wp_enqueue_scripts', static function () {
    if ( ! is_page() || ! is_room_type( get_queried_object_id() ) ) { return; }
    $base = dirname( __DIR__ );
    wp_enqueue_style( 'rivage-gallery', plugins_url( 'assets/gallery.css', $base . '/maison-rivage-core.php' ), array(), (string) filemtime( $base . '/assets/gallery.css' ) );
    wp_enqueue_script( 'rivage-gallery', plugins_url( 'assets/gallery.js', $base . '/maison-rivage-core.php' ), array(), (string) filemtime( $base . '/assets/gallery.js' ), true );
} );
