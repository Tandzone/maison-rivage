<?php
namespace MaisonRivage;

defined( 'ABSPATH' ) || exit;

/** Categories remain editable public Pages; physical rooms are private inventory. */
function register_catalogue() {
    add_post_type_support( 'page', 'excerpt' );
    register_post_type( 'rivage_room', array(
        'labels' => array(
            'name' => 'Rooms', 'singular_name' => 'Room', 'add_new_item' => 'Add Room',
            'edit_item' => 'Edit Room', 'all_items' => 'Rooms', 'not_found' => 'No rooms found.',
            'add_new' => 'Add Room', 'new_item' => 'New Room', 'search_items' => 'Search Rooms',
            'not_found_in_trash' => 'No rooms in Trash.', 'items_list' => 'Rooms list',
            'filter_items_list' => 'Filter rooms list', 'item_updated' => 'Room updated.',
            'item_published' => 'Room saved.',
        ),
        'public' => false, 'publicly_queryable' => false, 'exclude_from_search' => true,
        'show_ui' => true, 'show_in_rest' => false, 'show_in_menu' => 'maison-rivage',
        'rewrite' => false, 'query_var' => false, 'supports' => array( 'title', 'revisions' ),
        'map_meta_cap' => false,
        'capabilities' => array_fill_keys( array(
            'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
            'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts',
            'delete_published_posts', 'delete_others_posts', 'edit_private_posts',
            'edit_published_posts', 'create_posts',
        ), 'manage_options' ),
    ) );
    foreach ( array(
        'page' => array( '_rivage_room_type' => 'boolean', '_rivage_capacity' => 'integer',
            '_rivage_max_adults' => 'integer', '_rivage_max_children' => 'integer',
            '_rivage_outdoor' => 'string', '_rivage_rate_cents' => 'integer',
            '_rivage_private_events' => 'boolean' ),
        'rivage_room' => array( '_rivage_type_id' => 'integer', '_rivage_status' => 'string' ),
    ) as $post_type => $fields ) {
        foreach ( $fields as $key => $type ) {
            register_post_meta( $post_type, $key, array(
                'type' => $type, 'single' => true, 'show_in_rest' => false,
                'auth_callback' => static function () { return current_user_can( 'manage_options' ); },
            ) );
        }
    }
}

function room_types() {
    return get_posts( array( 'post_type' => 'page', 'post_status' => 'publish',
        'numberposts' => -1, 'meta_key' => '_rivage_room_type', 'meta_value' => '1',
        'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
}

function is_room_type( $id ) {
    return 'page' === get_post_type( $id ) && 'publish' === get_post_status( $id )
        && (bool) get_post_meta( $id, '_rivage_room_type', true );
}

/** Parse decimal euros without floating-point rounding or silently accepting malformed input. */
function parse_rate( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) { return null; }
    if ( ! preg_match( '/^([0-9]{1,5})(?:[.,]([0-9]{1,2}))?$/D', $value, $matches ) ) {
        return new \WP_Error( 'rate', 'Enter a positive nightly rate with at most two decimal places, or leave it blank.' );
    }
    $cents = (int) $matches[1] * 100 + (int) str_pad( $matches[2] ?? '', 2, '0' );
    return $cents > 0 ? $cents : new \WP_Error( 'rate', 'The nightly rate must be greater than zero.' );
}

function validate_type_fields( $input ) {
    $fields = array();
    foreach ( array( 'capacity' => array( 1, 12 ), 'max_adults' => array( 1, 12 ), 'max_children' => array( 0, 12 ) ) as $key => $range ) {
        $value = $input[ $key ] ?? '';
        if ( ! is_scalar( $value ) || ! preg_match( '/^[0-9]+$/D', (string) $value ) || (int) $value < $range[0] || (int) $value > $range[1] ) {
            return new \WP_Error( 'capacity', 'Enter whole-number guest limits within the displayed ranges.' );
        }
        $fields[ $key ] = (int) $value;
    }
    if ( $fields['max_adults'] > $fields['capacity'] || $fields['max_children'] > $fields['capacity']
        || $fields['max_adults'] + $fields['max_children'] < $fields['capacity'] ) {
        return new \WP_Error( 'capacity', 'Adult and child limits must fit within, and together cover, the total capacity.' );
    }
    if ( ! is_scalar( $input['rate'] ?? '' ) || ! is_scalar( $input['outdoor'] ?? '' ) ) {
        return new \WP_Error( 'fields', 'Invalid room type fields.' );
    }
    $fields['rate_cents'] = parse_rate( $input['rate'] ?? '' );
    if ( is_wp_error( $fields['rate_cents'] ) ) { return $fields['rate_cents']; }
    $fields['outdoor'] = sanitize_text_field( $input['outdoor'] ?? '' );
    if ( '' === $fields['outdoor'] || strlen( $fields['outdoor'] ) > 160 ) {
        return new \WP_Error( 'outdoor', 'Provide an outdoor-space description of up to 160 characters.' );
    }
    return $fields;
}

function validate_room_fields( $input ) {
    $type_id = $input['type_id'] ?? '';
    if ( ! is_scalar( $type_id ) || ! ctype_digit( (string) $type_id ) || ! is_room_type( (int) $type_id ) ) {
        return new \WP_Error( 'type', 'Select a published room type before saving the inventory details.' );
    }
    if ( ! in_array( $input['status'] ?? '', array( 'active', 'out_of_service' ), true ) ) {
        return new \WP_Error( 'status', 'Select a valid operational status.' );
    }
    return array( 'type_id' => (int) $type_id, 'status' => $input['status'] );
}

function type_data( $id ) {
    if ( ! is_room_type( $id ) ) { return null; }
    $capacity = (int) get_post_meta( $id, '_rivage_capacity', true );
    $adults = (int) get_post_meta( $id, '_rivage_max_adults', true );
    $children = (int) get_post_meta( $id, '_rivage_max_children', true );
    $label = $adults < $capacity && $children > 0
        ? sprintf( '%d %s + %d %s', $adults, 1 === $adults ? 'adult' : 'adults', $children, 1 === $children ? 'child' : 'children' )
        : sprintf( '%d guests', $capacity );
    return array( 'id' => (int) $id, 'title' => get_the_title( $id ), 'url' => get_permalink( $id ),
        'slug' => get_post_field( 'post_name', $id ), 'summary' => get_post_field( 'post_excerpt', $id ),
        'guests' => $label, 'capacity' => $capacity, 'outdoor' => get_post_meta( $id, '_rivage_outdoor', true ),
        'rate_cents' => metadata_exists( 'post', $id, '_rivage_rate_cents' ) ? (int) get_post_meta( $id, '_rivage_rate_cents', true ) : null,
        'image_id' => get_post_thumbnail_id( $id ) );
}

function format_rate( $cents ) {
    return number_format( $cents / 100, $cents % 100 ? 2 : 0, '.', ',' ) . ' €';
}
