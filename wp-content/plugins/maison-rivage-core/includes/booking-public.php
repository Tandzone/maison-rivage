<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;
function booking_value( $name, $default = '' ) {
    $input = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ? $_POST : $_GET;
    if ( ! isset( $input[ $name ] ) || ! is_scalar( $input[ $name ] ) ) { return $default; }
    $value = wp_unslash( (string) $input[ $name ] );
    return 'message' === $name ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
}
function booking_nonce_ok( $action ) {
    return isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $action );
}
function booking_public_routes() {
    if ( ! is_page() || ! in_array( get_page_uri( get_queried_object_id() ), array( 'booking', 'booking/verify', 'booking/request-received' ), true ) ) { return; }
    nocache_headers();
    header( 'Referrer-Policy: no-referrer' );
    header( 'X-Robots-Tag: noindex, nofollow' );
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { return; }
    $action = booking_value( 'rivage_booking_action' );
    if ( ! in_array( $action, array( 'request', 'verify', 'resend' ), true ) ) { return; }
    $error = null;
    if ( ! booking_nonce_ok( 'rivage_' . $action ) ) { $error = booking_error( 'nonce', 'This form expired. Reload the page and try again.' ); }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if ( ! $error && ! booking_limit( 'public:' . $action, $ip, 'verify' === $action ? 30 : 5 ) ) { $error = booking_error( 'limit', 'Too many attempts. Please try again later.' ); }
    if ( ! $error && '' !== booking_value( 'website' ) ) { $error = booking_error( 'form', 'The request could not be processed.' ); }
    if ( ! $error && 'request' === $action ) {
        $input = wp_unslash( $_POST );
        $result = booking_create( $input, booking_value( 'request_key' ) );
        if ( is_wp_error( $result ) ) { $error = $result; }
        else {
            dispatch_booking_mail();
            $receipt = bin2hex( random_bytes( 24 ) );
            set_transient( 'rivage_receipt_' . $receipt, array( 'reference' => $result->reference ), HOUR_IN_SECONDS );
            wp_safe_redirect( add_query_arg( 'receipt', $receipt, home_url( '/booking/request-received/' ) ) ); exit;
        }
    }
    if ( ! $error && 'verify' === $action ) {
        $result = booking_verify( booking_value( 'token' ) );
        if ( is_wp_error( $result ) ) { $error = $result; }
        else {
            dispatch_booking_mail();
            $GLOBALS['rivage_verification_success'] = true;
        }
    }
    if ( ! $error && 'resend' === $action ) {
        $email = booking_value( 'email' );
        if ( booking_limit( 'resend:email', strtolower( $email ), 3, 1800 ) ) {
            booking_resend( strtoupper( booking_value( 'reference' ) ), $email );
            dispatch_booking_mail();
        }
        $GLOBALS['rivage_resend_success'] = true;
    }
    $GLOBALS['rivage_booking_error'] = $error;
}
add_action( 'template_redirect', __NAMESPACE__ . '\\booking_public_routes' );
function booking_error_html() {
    $error = $GLOBALS['rivage_booking_error'] ?? null;
    return $error ? '<div class="rivage-notice" role="alert">' . esc_html( $error->get_error_message() ) . '</div>' : '';
}
function booking_search_fields() {
    $today = ( new \DateTimeImmutable( 'today', new \DateTimeZone( 'Indian/Antananarivo' ) ) )->format( 'Y-m-d' );
    ?>
    <div class="rivage-search-grid">
    <label>Check-in<input required type="date" name="check_in" min="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( booking_value( 'check_in' ) ); ?>"></label>
    <label>Check-out<input required type="date" name="check_out" min="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( booking_value( 'check_out' ) ); ?>"></label>
    <label>Adults <span>(12+)</span><input required type="number" name="adults" min="1" max="12" value="<?php echo esc_attr( booking_value( 'adults', '2' ) ); ?>"></label>
    <label>Children <span>(0–11)</span><input required type="number" name="children" min="0" max="12" value="<?php echo esc_attr( booking_value( 'children', '0' ) ); ?>"></label>
    <button class="wp-element-button" type="submit">Check Availability</button></div>
    <?php
}
function search_shortcode() {
    ob_start(); ?>
    <form class="rivage-search-form" method="get" action="<?php echo esc_url( home_url( '/booking/' ) ); ?>"><?php booking_search_fields(); ?></form>
    <?php return ob_get_clean();
}
function booking_shortcode() {
    $error = $GLOBALS['rivage_booking_error'] ?? null;
    $input = array( 'check_in' => booking_value( 'check_in' ), 'check_out' => booking_value( 'check_out' ),
        'adults' => booking_value( 'adults', '2' ), 'children' => booking_value( 'children', '0' ) );
    $has_search = '' !== $input['check_in'] || '' !== $input['check_out'];
    $stay = $has_search ? booking_dates( $input ) : null;
    $matches = array();
    if ( $stay && ! is_wp_error( $stay ) ) {
        foreach ( room_types() as $type ) {
            $quote = booking_quote( $type->ID, $stay );
            if ( $quote && available_rooms( $type->ID, $stay ) ) { $matches[] = array( $type, $quote ); }
        }
    }
    ob_start(); ?>
    <div class="rivage-booking-flow">
    <p class="rivage-demo-note">Fictional hotel demo. No real reservation or payment. Arrival from 14:00, departure before 11:00, Madagascar time.</p>
    <?php echo booking_error_html(); echo search_shortcode(); ?>
    <?php if ( is_wp_error( $stay ) ) : ?><p class="rivage-notice" role="alert"><?php echo esc_html( $stay->get_error_message() ); ?></p>
    <?php elseif ( $stay && ! $matches ) : ?><div class="rivage-notice"><h2>No rooms match these dates and guests.</h2><p>Try different dates or fewer guests. Your search values are preserved above.</p></div>
    <?php elseif ( $stay ) : ?>
    <h2>Choose your room category.</h2><p><?php echo (int) $stay['nights']; ?> night<?php echo 1 === $stay['nights'] ? '' : 's'; ?> · Pending requests do not hold rooms. Availability is checked again when the hotel confirms.</p>
    <form method="post" action="<?php echo esc_url( home_url( '/booking/' ) ); ?>" class="rivage-request-form">
    <?php wp_nonce_field( 'rivage_request' ); ?>
    <input type="hidden" name="rivage_booking_action" value="request"><input type="hidden" name="request_key" value="<?php echo esc_attr( booking_value( 'request_key' ) ?: booking_form_key() ); ?>">
    <?php foreach ( $input as $key => $value ) : ?><input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"><?php endforeach; ?>
    <fieldset class="rivage-room-options"><legend>Available categories</legend>
    <?php foreach ( $matches as list( $type, $quote ) ) : ?>
    <input type="hidden" name="quoted_rates[<?php echo (int) $type->ID; ?>]" value="<?php echo (int) $quote['rate_cents']; ?>">
    <label class="rivage-room-option"><input required type="radio" name="type_id" value="<?php echo (int) $type->ID; ?>" <?php checked( booking_value( 'type_id' ), (string) $type->ID ); ?>>
    <span><strong><?php echo esc_html( $type->post_title ); ?></strong><span><?php echo esc_html( format_rate( $quote['rate_cents'] ) ); ?> per night · <?php echo esc_html( format_rate( $quote['total_cents'] ) ); ?> demo total</span></span></label>
    <?php endforeach; ?></fieldset>
    <h2>Your details.</h2><div class="rivage-guest-fields">
    <label>Full name<input required name="guest_name" autocomplete="name" maxlength="120" value="<?php echo esc_attr( booking_value( 'guest_name' ) ); ?>"></label>
    <label>Email address<input required type="email" name="email" autocomplete="email" maxlength="254" value="<?php echo esc_attr( booking_value( 'email' ) ); ?>"></label></div>
    <label>Message (optional)<textarea name="message" maxlength="2000" rows="4"><?php echo esc_textarea( booking_value( 'message' ) ); ?></textarea></label>
    <div class="rivage-honeypot" aria-hidden="true"><label>Leave empty<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <label class="rivage-ack"><input required type="checkbox" name="demo_ack" value="1" <?php checked( booking_value( 'demo_ack' ), '1' ); ?>><span>I understand this is a fictional demo and my request requires manual confirmation. <a href="<?php echo esc_url( home_url( '/booking-policy/' ) ); ?>">Booking policy</a> · <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy</a></span></label>
    <p>The demo total is the nightly rate × nights, with no additional charges in this version. No payment is collected. You will first receive an email to verify your address.</p>
    <button class="wp-element-button" type="submit">Request a Reservation</button></form>
    <?php endif; ?>
    <details class="rivage-resend"><summary>Need a new verification email?</summary><?php echo resend_form(); ?></details>
    </div>
    <?php return ob_get_clean();
}
function resend_form() {
    ob_start(); ?>
    <?php if ( ! empty( $GLOBALS['rivage_resend_success'] ) ) : ?><p role="status">If those details match an unverified request, a new verification email will be sent. Use the latest link.</p><?php endif; ?>
    <form method="post" action="<?php echo esc_url( home_url( '/booking/verify/' ) ); ?>"><?php wp_nonce_field( 'rivage_resend' ); ?>
    <input type="hidden" name="rivage_booking_action" value="resend">
    <label>Request reference<input required name="reference" maxlength="16" autocomplete="off"></label>
    <label>Email address<input required type="email" name="email" autocomplete="email"></label>
    <button class="wp-element-button" type="submit">Send a New Link</button></form>
    <?php return ob_get_clean();
}
function received_shortcode() {
    $receipt = booking_value( 'receipt' );
    $data = preg_match( '/^[a-f0-9]{48}$/D', $receipt ) ? get_transient( 'rivage_receipt_' . $receipt ) : false;
    $html = '<div class="rivage-booking-state"><h2>Check your email.</h2><p>Open the verification link and press Verify Email. The link is valid for 30 minutes.</p>';
    if ( $data ) { $html .= '<p>Request reference: <strong>' . esc_html( $data['reference'] ) . '</strong></p>'; }
    return $html . '<p>Your request does not hold a room. After verification, the hotel will review availability and email its decision.</p><a href="' . esc_url( home_url( '/booking/verify/' ) ) . '">Request a new link if needed</a></div>';
}
function verify_shortcode() {
    if ( ! empty( $GLOBALS['rivage_verification_success'] ) ) {
        return '<div class="rivage-booking-state" role="status"><h2>Your email is verified.</h2><p>Your request is awaiting confirmation. The hotel has been notified. No room is held until the hotel confirms by email.</p></div>';
    }
    $token = booking_value( 'token' );
    ob_start(); echo booking_error_html(); ?>
    <div class="rivage-booking-state"><h2>Verify your email address.</h2><p>Verification confirms your email address, not a room reservation.</p>
    <?php if ( preg_match( '/^[a-f0-9]{64}$/D', $token ) && empty( $GLOBALS['rivage_resend_success'] ) ) : ?>
    <form method="post" action="<?php echo esc_url( home_url( '/booking/verify/' ) ); ?>"><?php wp_nonce_field( 'rivage_verify' ); ?>
    <input type="hidden" name="rivage_booking_action" value="verify"><input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>"><button class="wp-element-button">Verify Email</button></form>
    <?php endif; ?>
    <h3>Need a new link?</h3><?php echo resend_form(); ?></div>
    <?php return ob_get_clean();
}
add_shortcode( 'rivage_booking', __NAMESPACE__ . '\\booking_shortcode' );
add_shortcode( 'rivage_search', __NAMESPACE__ . '\\search_shortcode' );
add_shortcode( 'rivage_received', __NAMESPACE__ . '\\received_shortcode' );
add_shortcode( 'rivage_verify', __NAMESPACE__ . '\\verify_shortcode' );
add_action( 'wp_enqueue_scripts', static function () {
    if ( is_front_page() || is_page() ) {
        wp_enqueue_style( 'rivage-booking', plugins_url( 'assets/booking.css', dirname( __DIR__ ) . '/maison-rivage-core.php' ), array(), '0.3.0' );
    }
} );
add_filter( 'body_class', static function ( $classes ) {
    if ( is_page() && in_array( get_page_uri( get_queried_object_id() ), array( 'booking', 'booking/verify', 'booking/request-received' ), true ) ) { $classes[] = 'rivage-booking-page'; }
    return $classes;
} );
