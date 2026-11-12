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
