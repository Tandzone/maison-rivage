<?php
namespace MaisonRivage;

defined( 'ABSPATH' ) || exit;

function admin_menu() {
    add_menu_page( 'Maison Rivage', 'Maison Rivage', 'manage_options', 'maison-rivage', __NAMESPACE__ . '\\overview_screen', 'dashicons-palmtree', 25 );
    add_submenu_page( 'maison-rivage', 'Overview', 'Overview', 'manage_options', 'maison-rivage', __NAMESPACE__ . '\\overview_screen', 0 );
    add_submenu_page( 'maison-rivage', 'Room Types', 'Room Types', 'manage_options', 'rivage-room-types', __NAMESPACE__ . '\\types_screen' );
}

function overview_screen() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $rooms = get_posts( array( 'post_type' => 'rivage_room', 'post_status' => 'publish', 'numberposts' => -1 ) );
    $active = count( array_filter( $rooms, static function ( $room ) {
        return 'active' === get_post_meta( $room->ID, '_rivage_status', true )
            && is_room_type( (int) get_post_meta( $room->ID, '_rivage_type_id', true ) );
    } ) );
    ?>
    <div class="wrap"><h1>Maison Rivage</h1><p>Manage the room collection and physical inventory.</p>
    <table class="widefat striped" style="max-width:800px"><thead><tr><th>Inventory</th><th>Count</th></tr></thead><tbody>
        <tr><td>Published room types</td><td><?php echo count( room_types() ); ?></td></tr>
        <tr><td>Published physical rooms</td><td><?php echo count( $rooms ); ?></td></tr>
        <tr><td>Active rooms with a published category</td><td><?php echo (int) $active; ?></td></tr>
    </tbody></table>
    <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=rivage-room-types' ) ); ?>">Manage Room Types</a>
    <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=rivage_room' ) ); ?>">Manage Rooms</a></p>
    <h2>How the content is organized</h2><p>Room types are public pages: edit their photographs, description and layout with the block editor. Hotel details below the editor control their guest limits, outdoor space and nightly rate.</p>
    <p>Physical rooms are private inventory records. Their identifiers and operational status are not published on the website.</p>
    <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=rivage-bookings' ) ); ?>">Review reservation requests</a> · <a href="<?php echo esc_url( admin_url( 'admin.php?page=rivage-availability' ) ); ?>">View room availability</a> · <a href="<?php echo esc_url( admin_url( 'admin.php?page=rivage-notifications' ) ); ?>">Email notifications</a></p>
    <p>An active room is operational; this does not mean it is available for every date.</p></div>
    <?php
}

function types_screen() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $types = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
        'numberposts' => -1, 'meta_key' => '_rivage_room_type', 'meta_value' => '1', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
    ?>
    <div class="wrap"><h1>Room Types <a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rivage_add_type' ), 'rivage_add_type' ) ); ?>">Add Room Type</a></h1>
    <p>Public category pages and their hotel details. Prices are in EUR per room, per night.</p>
    <table class="widefat striped"><thead><tr><th>Room type</th><th>Guests</th><th>Outdoor space</th><th>Nightly rate</th><th>Physical rooms</th><th>Status</th></tr></thead><tbody>
    <?php foreach ( $types as $type ) :
        $count = new \WP_Query( array( 'post_type' => 'rivage_room', 'post_status' => 'publish', 'posts_per_page' => 1,
            'fields' => 'ids', 'meta_key' => '_rivage_type_id', 'meta_value' => $type->ID ) );
        $rate = get_post_meta( $type->ID, '_rivage_rate_cents', true ); ?>
        <tr><td><strong><a href="<?php echo esc_url( get_edit_post_link( $type->ID ) ); ?>"><?php echo esc_html( $type->post_title ); ?></a></strong>
        <?php if ( 'publish' === $type->post_status ) : ?> · <a href="<?php echo esc_url( get_permalink( $type->ID ) ); ?>">View page</a><?php endif; ?></td>
        <td><?php echo (int) get_post_meta( $type->ID, '_rivage_capacity', true ); ?></td>
        <td><?php echo esc_html( get_post_meta( $type->ID, '_rivage_outdoor', true ) ); ?></td>
        <td><?php echo '' === $rate ? 'Not set' : esc_html( format_rate( (int) $rate ) ); ?></td>
        <td><?php echo (int) $count->found_posts; ?></td><td><?php echo esc_html( get_post_status_object( $type->post_status )->label ); ?></td></tr>
    <?php endforeach; ?>
    <?php if ( ! $types ) : ?><tr><td colspan="6">No room types yet. Add a category to get started.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php
}

function add_type() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'You cannot manage room types.', '', array( 'response' => 403 ) ); }
    check_admin_referer( 'rivage_add_type' );
    $parent = get_page_by_path( 'rooms' );
    if ( ! $parent ) { wp_die( 'Create the Rooms & Suites parent page first.' ); }
    $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_parent' => $parent->ID,
        'post_title' => 'New Room Type', 'post_content' => '<!-- wp:post-title {"level":1} /--><!-- wp:shortcode -->[rivage_room_facts]<!-- /wp:shortcode -->' ), true );
    if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ) ); }
    update_post_meta( $id, '_rivage_room_type', 1 );
    wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
    exit;
}

function is_type_editor( $post ) {
    $parent = get_page_by_path( 'rooms' );
    return $post && 'page' === $post->post_type && ( get_post_meta( $post->ID, '_rivage_room_type', true )
        || ( $parent && (int) $post->post_parent === $parent->ID ) );
}

function meta_boxes( $post_type, $post ) {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    if ( is_type_editor( $post ) ) { add_meta_box( 'rivage-type-details', 'Maison Rivage — Room Type Details', __NAMESPACE__ . '\\type_box', 'page', 'normal', 'high' ); }
    if ( 'rivage_room' === $post_type ) { add_meta_box( 'rivage-room-details', 'Room Inventory', __NAMESPACE__ . '\\room_box', 'rivage_room', 'normal', 'high' ); }
}

function type_box( $post ) {
    wp_nonce_field( 'rivage_type_' . $post->ID, 'rivage_type_nonce' );
    inline_error( $post->ID );
    $capacity = get_post_meta( $post->ID, '_rivage_capacity', true );
    $adults = get_post_meta( $post->ID, '_rivage_max_adults', true );
    $children = get_post_meta( $post->ID, '_rivage_max_children', true );
    $rate = get_post_meta( $post->ID, '_rivage_rate_cents', true );
    ?>
    <p>These details feed the public room collection and room facts. Edit the page excerpt for the short description, and use Featured image for its listing photograph.</p>
    <table class="form-table"><tbody>
    <?php foreach ( array( 'capacity' => array( 'Total guest capacity', $capacity, 1 ), 'max_adults' => array( 'Maximum adults', $adults, 1 ), 'max_children' => array( 'Maximum children', $children, 0 ) ) as $key => $field ) : ?>
        <tr><th><label for="rivage-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th><td><input id="rivage-<?php echo esc_attr( $key ); ?>" name="rivage_type[<?php echo esc_attr( $key ); ?>]" type="number" min="<?php echo (int) $field[2]; ?>" max="12" required value="<?php echo esc_attr( $field[1] ); ?>"></td></tr>
    <?php endforeach; ?>
    <tr><th><label for="rivage-outdoor">Outdoor space</label></th><td><input class="regular-text" id="rivage-outdoor" name="rivage_type[outdoor]" maxlength="160" required value="<?php echo esc_attr( get_post_meta( $post->ID, '_rivage_outdoor', true ) ); ?>"></td></tr>
    <tr><th><label for="rivage-rate">Nightly rate (EUR)</label></th><td><input id="rivage-rate" name="rivage_type[rate]" inputmode="decimal" pattern="[0-9]{1,5}([.,][0-9]{1,2})?" title="Use a positive amount with at most two decimal places." placeholder="Leave blank to hide" value="<?php echo '' === $rate ? '' : esc_attr( number_format( (int) $rate / 100, 2, '.', '' ) ); ?>"><p class="description">Per room, per night. No payment is collected.</p></td></tr>
    </tbody></table><p class="description">Adult and child limits are separate maxima; the total guest capacity always applies. Private room events are reserved for Rivage Suites.</p>
    <?php
}

function room_box( $post ) {
    wp_nonce_field( 'rivage_room_' . $post->ID, 'rivage_room_nonce' );
    inline_error( $post->ID );
    $type_id = (int) get_post_meta( $post->ID, '_rivage_type_id', true );
    $status = get_post_meta( $post->ID, '_rivage_status', true ) ?: 'out_of_service';
    ?>
    <p>The title is an internal room identifier. These records have no public page.</p>
    <p><label for="rivage-type-id"><strong>Room type</strong></label><br><select id="rivage-type-id" name="rivage_room[type_id]" required><option value="">Select a published category</option>
    <?php foreach ( room_types() as $type ) : ?><option value="<?php echo (int) $type->ID; ?>" <?php selected( $type_id, $type->ID ); ?>><?php echo esc_html( $type->post_title ); ?></option><?php endforeach; ?>
    </select></p><p><label for="rivage-status"><strong>Operational status</strong></label><br><select id="rivage-status" name="rivage_room[status]">
    <option value="active" <?php selected( $status, 'active' ); ?>>Active</option><option value="out_of_service" <?php selected( $status, 'out_of_service' ); ?>>Out of service</option></select></p>
    <p class="description">Operational status is not a date-based availability check. Unconfigured rooms do not count as active inventory.</p>
    <?php
}

function save_error( $post_id, $message ) {
    set_transient( 'rivage_save_error_' . get_current_user_id() . '_' . $post_id, $message, 90 );
}

function inline_error( $post_id ) {
    $message = get_transient( 'rivage_save_error_' . get_current_user_id() . '_' . $post_id );
    if ( $message ) { echo '<div class="notice notice-error inline"><p><strong>Hotel details were not saved.</strong> ' . esc_html( $message ) . '</p></div>'; }
}

function save_details( $post_id, $post ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( is_type_editor( $post ) && isset( $_POST['rivage_type_nonce'] ) ) {
        if ( ! is_string( $_POST['rivage_type_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rivage_type_nonce'] ) ), 'rivage_type_' . $post_id ) ) { return; }
        $fields = validate_type_fields( isset( $_POST['rivage_type'] ) && is_array( $_POST['rivage_type'] ) ? wp_unslash( $_POST['rivage_type'] ) : array() );
        if ( is_wp_error( $fields ) ) { save_error( $post_id, $fields->get_error_message() ); return; }
        delete_transient( 'rivage_save_error_' . get_current_user_id() . '_' . $post_id );
        foreach ( $fields as $key => $value ) {
            if ( null === $value ) { delete_post_meta( $post_id, '_rivage_' . $key ); }
            else { update_post_meta( $post_id, '_rivage_' . $key, $value ); }
        }
        update_post_meta( $post_id, '_rivage_room_type', 1 );
    }
    if ( 'rivage_room' === $post->post_type && isset( $_POST['rivage_room_nonce'] ) ) {
        if ( ! is_string( $_POST['rivage_room_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rivage_room_nonce'] ) ), 'rivage_room_' . $post_id ) ) { return; }
        $fields = validate_room_fields( isset( $_POST['rivage_room'] ) && is_array( $_POST['rivage_room'] ) ? wp_unslash( $_POST['rivage_room'] ) : array() );
        if ( is_wp_error( $fields ) ) { save_error( $post_id, $fields->get_error_message() ); return; }
        delete_transient( 'rivage_save_error_' . get_current_user_id() . '_' . $post_id );
        update_post_meta( $post_id, '_rivage_type_id', $fields['type_id'] );
        update_post_meta( $post_id, '_rivage_status', $fields['status'] );
    }
}

function error_notice() {
    $id = isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
    if ( ! $id || ! current_user_can( 'manage_options' ) ) { return; }
    $key = 'rivage_save_error_' . get_current_user_id() . '_' . $id;
    $message = get_transient( $key );
    if ( $message ) {
        delete_transient( $key );
        echo '<div class="notice notice-error"><p><strong>Hotel details were not saved.</strong> ' . esc_html( $message ) . '</p></div>';
    }
}

function room_columns( $columns ) {
    return array( 'cb' => $columns['cb'], 'title' => 'Room identifier', 'rivage_type' => 'Room type', 'rivage_status' => 'Operational status', 'date' => $columns['date'] );
}
function room_column( $column, $id ) {
    if ( 'rivage_type' === $column ) {
        $type_id = (int) get_post_meta( $id, '_rivage_type_id', true );
        echo is_room_type( $type_id ) ? esc_html( get_the_title( $type_id ) ) : '<strong>Not configured / unpublished category</strong>';
    }
    if ( 'rivage_status' === $column ) {
        $status = get_post_meta( $id, '_rivage_status', true );
        echo 'active' === $status ? 'Active' : ( 'out_of_service' === $status ? 'Out of service' : 'Not configured' );
    }
}

add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );
add_action( 'admin_post_rivage_add_type', __NAMESPACE__ . '\\add_type' );
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\meta_boxes', 10, 2 );
add_action( 'save_post', __NAMESPACE__ . '\\save_details', 10, 2 );
add_action( 'admin_notices', __NAMESPACE__ . '\\error_notice' );
add_filter( 'manage_rivage_room_posts_columns', __NAMESPACE__ . '\\room_columns' );
add_action( 'manage_rivage_room_posts_custom_column', __NAMESPACE__ . '\\room_column', 10, 2 );

function room_filters( $post_type ) {
    if ( 'rivage_room' !== $post_type ) { return; }
    $selected_type = isset( $_GET['rivage_type'] ) && is_scalar( $_GET['rivage_type'] ) ? absint( $_GET['rivage_type'] ) : 0;
    $selected_status = isset( $_GET['rivage_status'] ) && is_string( $_GET['rivage_status'] ) ? sanitize_key( $_GET['rivage_status'] ) : '';
    echo '<label class="screen-reader-text" for="rivage-filter-type">Filter by room type</label><select id="rivage-filter-type" name="rivage_type"><option value="">All room types</option>';
    foreach ( room_types() as $type ) {
        echo '<option value="' . (int) $type->ID . '" ' . selected( $selected_type, $type->ID, false ) . '>' . esc_html( $type->post_title ) . '</option>';
    }
    echo '</select><label class="screen-reader-text" for="rivage-filter-status">Filter by operational status</label><select id="rivage-filter-status" name="rivage_status"><option value="">All operational statuses</option>';
    foreach ( array( 'active' => 'Active', 'out_of_service' => 'Out of service' ) as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected_status, $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}
function filter_room_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || 'rivage_room' !== $query->get( 'post_type' ) ) { return; }
    $meta = array();
    if ( isset( $_GET['rivage_type'] ) && is_scalar( $_GET['rivage_type'] ) && absint( $_GET['rivage_type'] ) ) {
        $meta[] = array( 'key' => '_rivage_type_id', 'value' => absint( $_GET['rivage_type'] ), 'type' => 'NUMERIC' );
    }
    if ( isset( $_GET['rivage_status'] ) && in_array( $_GET['rivage_status'], array( 'active', 'out_of_service' ), true ) ) {
        $meta[] = array( 'key' => '_rivage_status', 'value' => $_GET['rivage_status'] );
    }
    if ( $meta ) { $query->set( 'meta_query', $meta ); }
}
add_action( 'restrict_manage_posts', __NAMESPACE__ . '\\room_filters' );
add_action( 'pre_get_posts', __NAMESPACE__ . '\\filter_room_query' );
