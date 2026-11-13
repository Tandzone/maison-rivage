<?php
/** Presentation assets for the Maison Rivage child theme. */
defined( 'ABSPATH' ) || exit;

add_action(
    'enqueue_block_assets',
    static function () {
        wp_enqueue_style(
            'maison-rivage',
            get_stylesheet_directory_uri() . '/assets/site.css',
            array(),
            (string) filemtime( get_stylesheet_directory() . '/assets/site.css' )
        );
        wp_enqueue_style(
            'maison-rivage-refinements',
            get_stylesheet_directory_uri() . '/assets/refinements.css',
            array( 'maison-rivage' ),
            (string) filemtime( get_stylesheet_directory() . '/assets/refinements.css' )
        );
    }
);

// Keep saved image blocks valid while adding delivery attributes on the frontend.
add_filter(
    'render_block_core/image',
    static function ( $html, $block ) {
        $processor = new WP_HTML_Tag_Processor( $html );
        if ( ! $processor->next_tag( 'img' ) ) {
            return $html;
        }
        $src = (string) $processor->get_attribute( 'src' );
        $base = get_stylesheet_directory_uri() . '/assets/images/';
        if ( ! str_starts_with( $src, $base ) ) {
            return $html;
        }
        $dimensions = array(
            'coastal-suite.png' => array( 1774, 887 ),
            'hall.png' => array( 1672, 941 ),
            'cello-evening.png' => array( 1672, 941 ),
        );
        $name = substr( $src, strlen( $base ) );
        if ( ! isset( $dimensions[ $name ] ) ) {
            return $html;
        }
        if ( is_readable( get_stylesheet_directory() . '/assets/images/' . str_replace( '.png', '.webp', $name ) ) ) {
            $processor->set_attribute( 'src', $base . str_replace( '.png', '.webp', $name ) );
        }
        $processor->set_attribute( 'width', $dimensions[ $name ][0] );
        $processor->set_attribute( 'height', $dimensions[ $name ][1] );
        $hero = str_contains( $block['attrs']['className'] ?? '', 'rivage-hero-image' );
        $processor->set_attribute( 'loading', $hero ? 'eager' : 'lazy' );
        if ( $hero ) {
            $processor->set_attribute( 'fetchpriority', 'high' );
        }
        return $processor->get_updated_html();
    },
    10,
    2
);

// Serve compact derivatives for untouched seeded media; preserve edited/replaced attachments.
add_filter( 'wp_get_attachment_image_attributes', static function ( $attrs, $attachment, $size ) {
    if ( is_admin() || ! in_array( $size, array( 'large', 'full', 'post-thumbnail' ), true ) ) { return $attrs; }
    $file = get_post_meta( $attachment->ID, '_rivage_seed_asset', true );
    $dimensions = array( 'coastal-suite.png' => 1774, 'hall.png' => 1672, 'cello-evening.png' => 1672 );
    if ( ! isset( $dimensions[ $file ] ) || basename( (string) get_attached_file( $attachment->ID ) ) !== $file || get_post_meta( $attachment->ID, '_wp_attachment_backup_sizes', true ) ) { return $attrs; }
    $stem = pathinfo( $file, PATHINFO_FILENAME );
    $base = get_stylesheet_directory_uri() . '/assets/images/' . $stem;
    $attrs['src'] = $base . '.webp';
    $attrs['srcset'] = $base . '-640.webp 640w, ' . $base . '-1200.webp 1200w, ' . $base . '.webp ' . $dimensions[ $file ] . 'w';
    $attrs['sizes'] = '(max-width: 600px) 100vw, (max-width: 1000px) 75vw, 1000px';
    return $attrs;
}, 10, 3 );
