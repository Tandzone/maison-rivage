<?php
$hashes = json_decode( file_get_contents( __DIR__ . '/polish-preview-hashes.json' ), true );
foreach ( $hashes as $path => $hash ) {
    $page = get_page_by_path( $path );
    $slug = 'privacy-policy' === $path ? 'privacy' : $path;
    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'maison-rivage/' . $slug );
    if ( ! $page || ! $pattern || get_post_meta( $page->ID, '_rivage_polish_page', true ) ) { continue; }
    if ( hash_equals( $hash, hash( 'sha256', str_replace( home_url(), '{site-url}', $page->post_content ) ) ) ) {
        update_post_meta( $page->ID, '_rivage_pre_polish_content', $page->post_content );
        wp_update_post( array( 'ID' => $page->ID, 'post_content' => $pattern['content'] ) );
        update_post_meta( $page->ID, '_rivage_polish_page', 1 );
    } elseif ( $page->post_content === $pattern['content'] ) { update_post_meta( $page->ID, '_rivage_polish_page', 1 ); }
    else { WP_CLI::warning( 'Preserving edited page; review contact form/privacy updates manually: ' . $path ); }
}
WP_CLI::success( 'Contact and privacy pages ready; edited content preserved.' );
