<?php
/** Block transformations for untouched preview pages, shared by source generation and migration. */
function rivage_content_blocks( $blocks, $kind ) {
    foreach ( $blocks as &$block ) {
        $class = $block['attrs']['className'] ?? '';
        if ( 'core/group' === $block['blockName'] && 'home' === $kind && str_contains( $class, 'rivage-journal-teaser' ) ) {
            $has_query = array_filter( $block['innerBlocks'], static function ( $child ) { return 'core/query' === $child['blockName']; } );
            if ( ! $has_query ) {
                $query = '<!-- wp:query {"query":{"perPage":3,"postType":"post","order":"desc","orderBy":"date","inherit":false},"className":"rivage-journal-grid"} --><div class="wp-block-query rivage-journal-grid"><!-- wp:post-template --><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /--><!-- wp:post-title {"level":3,"isLink":true} /--><!-- wp:post-excerpt {"moreText":"","excerptLength":24} /--><!-- /wp:post-template --><!-- wp:query-no-results --><!-- wp:paragraph --><p>Our first stories will appear here soon.</p><!-- /wp:paragraph --><!-- /wp:query-no-results --></div><!-- /wp:query -->';
                $block['innerBlocks'][] = parse_blocks( $query )[0];
                $closing = array_pop( $block['innerContent'] );
                $block['innerContent'][] = null;
                $block['innerContent'][] = $closing;
            }
        }
        if ( 'core/group' === $block['blockName'] && 'home' === $kind && 'rivage-room-grid' === $class ) {
            $block = parse_blocks( '<!-- wp:shortcode -->[rivage_rooms layout="cards"]<!-- /wp:shortcode -->' )[0];
            continue;
        }
        if ( 'core/group' === $block['blockName'] && 'rooms' === $kind && str_contains( $class, 'rivage-room-catalogue' ) ) {
            $block = parse_blocks( '<!-- wp:group {"className":"rivage-wrap","layout":{"type":"default"}} --><div class="wp-block-group rivage-wrap"><!-- wp:shortcode -->[rivage_rooms layout="catalogue"]<!-- /wp:shortcode --></div><!-- /wp:group -->' )[0];
            continue;
        }
        if ( 'core/html' === $block['blockName'] && str_contains( $block['innerHTML'], 'class="rivage-room-facts"' ) ) {
            $block = parse_blocks( '<!-- wp:shortcode -->[rivage_room_facts]<!-- /wp:shortcode -->' )[0];
            continue;
        }
        if ( 'type' === $kind && 'core/heading' === $block['blockName'] && 1 === ( $block['attrs']['level'] ?? 2 ) ) {
            $block = parse_blocks( '<!-- wp:post-title {"level":1} /-->' )[0];
            continue;
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = rivage_content_blocks( $block['innerBlocks'], $kind );
        }
    }
    return $blocks;
}
