<?php
namespace MaisonRivage;
defined( 'ABSPATH' ) || exit;
function contact_submit( $input, $key ) {
    global $wpdb;
    if ( ! booking_valid_key( $key ) ) { return booking_error( 'form', 'This form expired. Reload the page and try again.' ); }
    $event = '0:contact:' . hash( 'sha256', $key );
    $existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . booking_table( 'mail' ) . ' WHERE event_key=%s', $event ) );
    if ( $existing ) { return (int) $existing; }
    foreach ( array( 'guest_name', 'email', 'message', 'demo_ack' ) as $field ) {
        if ( ! isset( $input[ $field ] ) || ! is_string( $input[ $field ] ) ) { return booking_error( 'fields', 'Complete your name, email and message, and acknowledge the demonstration notice.' ); }
    }
    $name = sanitize_text_field( $input['guest_name'] );
    $email = $input['email'];
    $message = sanitize_textarea_field( $input['message'] );
    if ( '' === trim( $name ) || strlen( $name ) > 120 || strlen( $email ) > 254 || ! is_email( $email ) || preg_match( '/[\r\n]/', $email ) || strlen( trim( $message ) ) < 10 || strlen( $message ) > 3000 || '1' !== $input['demo_ack'] ) {
        return booking_error( 'fields', 'Enter a name (up to 120 characters), a valid email and a message of 10–3,000 characters. Acknowledge the demo notice.' );
    }
    $recipient = get_option( 'admin_email' );
    if ( ! is_email( $recipient ) ) { return booking_error( 'mail', 'Contact is temporarily unavailable. Please try again later.' ); }
    $body = "Maison Rivage — fictional contact demonstration\nName: $name\nEmail: $email\n\n$message\n\nNo reservation has been requested or confirmed. Reply manually using the email above.";
    // INSERT IGNORE plus a unique event key makes concurrent retries idempotent.
    $result = $wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . booking_table( 'mail' ) . " (booking_id,event_key,recipient,subject,body,status,next_attempt,created_at) VALUES (0,%s,%s,%s,%s,'queued',%d,%d)", $event, $recipient, 'Maison Rivage — contact demonstration', $body, time(), time() ) );
    $id = false === $result ? 0 : $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . booking_table( 'mail' ) . ' WHERE event_key=%s', $event ) );
    return $id ? (int) $id : booking_error( 'storage', 'Your message could not be queued. Please try again.' );
}
add_action( 'template_redirect', static function () {
    if ( ! is_page( 'contact' ) ) { return; }
    nocache_headers(); header( 'Referrer-Policy: no-referrer' ); header( 'X-Robots-Tag: noindex, nofollow' );
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || 'contact' !== booking_value( 'rivage_contact_action' ) ) { return; }
    $error = null;
    if ( ! booking_nonce_ok( 'rivage_contact' ) ) { $error = booking_error( 'nonce', 'This form expired. Reload the page and try again.' ); }
    if ( ! $error && ! booking_limit( 'public:contact', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 5 ) ) { $error = booking_error( 'limit', 'Too many attempts. Please try again later.' ); }
    if ( ! $error && '' !== booking_value( 'website' ) ) { $error = booking_error( 'form', 'The message could not be processed.' ); }
    if ( ! $error ) {
        $result = contact_submit( wp_unslash( $_POST ), booking_value( 'request_key' ) );
        if ( is_wp_error( $result ) ) { $error = $result; }
        else {
            dispatch_booking_mail();
            $receipt = bin2hex( random_bytes( 24 ) ); set_transient( 'rivage_contact_receipt_' . $receipt, 1, HOUR_IN_SECONDS );
            wp_safe_redirect( add_query_arg( 'receipt', $receipt, home_url( '/contact/' ) ) ); exit;
        }
    }
    $GLOBALS['rivage_contact_error'] = $error;
} );
add_shortcode( 'rivage_contact', static function () {
    $receipt = booking_value( 'receipt' );
    if ( preg_match( '/^[a-f0-9]{48}$/D', $receipt ) && get_transient( 'rivage_contact_receipt_' . $receipt ) ) {
        return '<div class="rivage-notice" role="status"><h2>Message queued.</h2><p>Your message has been saved to the demo email queue. This does not create a reservation. In this local version, email stays in the test mailbox.</p></div>';
    }
    $error = $GLOBALS['rivage_contact_error'] ?? null;
    ob_start(); ?>
    <div class="rivage-booking-flow rivage-contact-flow">
    <?php if ( $error ) { ?><div class="rivage-notice" role="alert"><?php echo esc_html( $error->get_error_message() ); ?></div><?php } ?>
    <h2>Leave a note.</h2><p id="contact-demo-note">Fictional hotel demonstration. Use test details. This form does not make a reservation; messages are captured in the local test mailbox.</p>
    <form method="post" action="<?php echo esc_url( home_url( '/contact/' ) ); ?>" aria-describedby="contact-demo-note">
    <?php wp_nonce_field( 'rivage_contact' ); ?>
    <input type="hidden" name="rivage_contact_action" value="contact"><input type="hidden" name="request_key" value="<?php echo esc_attr( booking_valid_key( booking_value( 'request_key' ) ) ? booking_value( 'request_key' ) : booking_form_key() ); ?>">
    <div class="rivage-guest-fields"><label>Full name<input required name="guest_name" maxlength="120" autocomplete="name" value="<?php echo esc_attr( booking_value( 'guest_name' ) ); ?>"></label><label>Email address<input required type="email" name="email" maxlength="254" autocomplete="email" value="<?php echo esc_attr( booking_value( 'email' ) ); ?>"></label></div>
    <label>Message<textarea required name="message" rows="6" minlength="10" maxlength="3000"><?php echo esc_textarea( booking_value( 'message' ) ); ?></textarea></label>
    <div class="rivage-honeypot" aria-hidden="true"><label>Leave this field empty<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <label class="rivage-ack"><input required type="checkbox" name="demo_ack" value="1" <?php checked( booking_value( 'demo_ack' ), '1' ); ?>><span>I understand this is a fictional demonstration. <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy policy</a></span></label>
    <button class="wp-element-button" type="submit">Send Demo Message</button></form></div>
    <?php return ob_get_clean();
} );
add_action( 'rivage_mail_tick', static function () {
    global $wpdb;
    $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . booking_table( 'mail' ) . " WHERE booking_id=0 AND event_key LIKE '0:contact:%%' AND status<>'sending' AND created_at>0 AND created_at<%d", time() - DAY_IN_SECONDS ) );
} );
