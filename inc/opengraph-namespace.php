<?php
/**
 * Opengraph meta tag handling.
 *
 * @package hm-platform/seo
 */

namespace HM\MetaTags\Opengraph;

use function HM\MetaTags\get_meta_for_context;
use function HM\MetaTags\to_meta_tags;

function bootstrap() {
	add_filter( 'hm.metatags.context.opengraph.author', __NAMESPACE__ . '\\author', 10, 2 );
	add_filter( 'hm.metatags.context.opengraph.front_page', __NAMESPACE__ . '\\front_page', 10, 2 );
	add_filter( 'hm.metatags.context.opengraph.singular', __NAMESPACE__ . '\\singular', 10, 2 );
	add_filter( 'language_attributes', __NAMESPACE__ . '\\add_xmlns' );
	add_action( 'wp_head', __NAMESPACE__ . '\\to_html' );
}

/**
 * Add opengraph namespace to html tag.
 *
 * @param string $xmlns
 * @return string
 */
function add_xmlns( string $xmlns ) : string {
	return $xmlns . ' xmlns:og="http://ogp.me/ns#"';
}

/**
 * Set up default meta data shared across all pages.
 *
 * @return array
 */
function get_default_meta( array $meta, array $context ) : array {
	$meta['site_name'] = get_bloginfo( 'name' );
	$meta['locale'] = $context['locale'];
	$meta['type'] = 'website';
	$meta['title'] = $context['title'];
	$meta['description'] = $context['description'];
	$meta['image'] = $context['image'];
	$meta['url'] = $context['url'];

	if ( ! empty( $context['image'] ) && isset( $context['image_id'] ) ) {
		$meta['image:alt'] = get_post_meta( $context['image_id'], '_wp_attachment_image_alt', true );
	}

	return $meta;
}

/**
 * Default meta data for the front page.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function front_page( array $meta, array $context ) : array {
	return get_default_meta( $meta, $context );
}

/**
 * Extended metadata for single posts / pages.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function singular( array $meta, array $context ) : array {
	$meta = get_default_meta( $meta, $context );

	// Treat non hierarchical posts as articles by default.
	if ( is_post_type_hierarchical( $context['object']->post_type ) ) {
		return $meta;
	}

	$meta['type'] = 'article';
	$meta['article:published_time'] = get_the_date( 'c', $context['object'] );
	$meta['article:modified_time'] = get_the_modified_date( 'c', $context['object'] );
	$meta['article:expiration_time'] = false;
	$meta['article:author'] = get_author_posts_url( $context['object']->post_author );

	if ( $context['taxonomies']['category'] ?? false ) {
		$meta['article:section'] = array_shift( $context['taxonomies']['category'] );
	}

	if ( $context['taxonomies']['post_tag'] ?? false ) {
		$meta['article:tag'] = $context['taxonomies']['post_tag'];
	}

	return $meta;
}

/**
 * Author profile open graph tags.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function author( array $meta, array $context ) : array {
	$meta = get_default_meta( $meta, $context );
	$meta['type'] = 'profile';
	$meta['profile:first_name'] = $context['object']->get( 'first_name' );
	$meta['profile:last_name'] = $context['object']->get( 'last_name' );

	return $meta;
}

/**
 * Output the opengraph meta tags.
 */
function to_html() {
	$meta = get_meta_for_context( 'opengraph' );
	to_meta_tags( $meta, 'og:', 'property' );
}
