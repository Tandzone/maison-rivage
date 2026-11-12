<?php
/** Readable fallback markup, also styled by the Maison Rivage child theme. */
defined( 'ABSPATH' ) || exit;
$catalogue = 'catalogue' === $layout;
?>
<div class="<?php echo $catalogue ? 'rivage-room-catalogue' : 'rivage-room-grid'; ?>">
<?php foreach ( $rooms as $index => $room ) : ?>
    <article class="<?php echo $catalogue ? 'rivage-room-entry' : 'rivage-room-card'; ?>">
        <?php if ( $room['image_id'] ) : ?>
        <figure class="<?php echo esc_attr( $catalogue ? 'rivage-catalogue-image rivage-catalogue-image-' . $index : 'rivage-room-image rivage-room-image-' . $index ); ?>">
            <?php echo wp_get_attachment_image( $room['image_id'], 'large', false, array( 'loading' => 'lazy' ) ); ?>
        </figure>
        <?php endif; ?>
        <div class="<?php echo $catalogue ? 'rivage-catalogue-copy' : 'rivage-card-copy'; ?>">
            <p class="<?php echo $catalogue ? 'rivage-eyebrow' : 'rivage-meta'; ?>"><?php echo esc_html( $room['guests'] . ' · ' . $room['outdoor'] ); ?></p>
            <?php if ( $catalogue ) : ?><h2><?php echo esc_html( $room['title'] ); ?></h2>
            <?php else : ?><h3><?php echo esc_html( $room['title'] ); ?></h3><?php endif; ?>
            <p><?php echo esc_html( $room['summary'] ); ?></p>
            <?php if ( $catalogue ) : ?>
                <?php echo \MaisonRivage\room_facts_html( $room ); ?>
            <?php elseif ( null !== $room['rate_cents'] ) : ?>
                <p class="rivage-card-rate"><?php echo esc_html( \MaisonRivage\format_rate( $room['rate_cents'] ) ); ?> <span>per night</span></p>
            <?php endif; ?>
            <p class="rivage-text-link"><a href="<?php echo esc_url( $room['url'] ); ?>">Discover the room <span aria-hidden="true">↗</span></a></p>
        </div>
    </article>
<?php endforeach; ?>
</div>
