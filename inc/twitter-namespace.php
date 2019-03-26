<?php
/**
 * Twitter card meta tags.
 *
 * @package hm-metatags
 */

namespace HM\MetaTags\Twitter;

use function HM\MetaTags\get_meta_for_context;
use function HM\MetaTags\get_social_urls;
use function HM\MetaTags\to_meta_tags;

function bootstrap() {
	add_filter( 'hm.metatags.context.twitter.front_page', __NAMESPACE__ . '\\front_page', 10, 2 );
	add_filter( 'hm.metatags.context.twitter.singular', __NAMESPACE__ . '\\singular', 10, 2 );
	add_filter( 'user_contactmethods', __NAMESPACE__ . '\\add_contact_method' );
	add_action( 'wp_head', __NAMESPACE__ . '\\to_html' );
}

/**
 * Add a contact method for twitter username / URL.
 *
 * @param array $methods
 * @return array
 */
function add_contact_method( array $methods ) : array {
	$methods['twitter'] = esc_html__( 'Twitter' );
	return $methods;
}

/**
 * Sanitize a twitter URL or username to the @handle version.
 *
 * @param string $string
 * @return string
 */
function sanitize_username( string $string ) : string {
	if ( empty( $string ) ) {
		return '';
	}

	// URLs.
	if ( strpos( $string, 'twitter.com' ) !== false ) {
		$string = preg_replace( '#^https://twitter\.com/([A-Za-z0-9_]{1,15})$#', '$1', $string );
	}

	return '@' . substr( ltrim( $string, '@' ), 0, 15 );
}

/**
 * Get common values.
 *
 * @param array $context
 * @return array
 */
function get_default_meta( array $context ) : array {
	$meta = [];
	$meta['card'] = $context['image'] ? 'summary_large_image' : 'summary';
	$meta['site'] = sanitize_username( get_social_urls()['twitter'] ?? '' );
	$meta['title'] = $context['title'];
	$meta['description'] = $context['description'];
	$meta['image'] = $context['image'];

	return $meta;
}

/**
 * Set the front page meta data.
 *
 * @param array $meta
 * @param array $context
 * @return array Key value pairs of meta tags.
 */
function front_page( array $meta, array $context ) : array {
	$meta = get_default_meta( $context );
	return $meta;
}

/**
 * Meta data for singular posts / pages.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function singular( array $meta, array $context ) : array {
	$meta = get_default_meta( $context );
	$author = get_user_by( 'id', $context['object']->post_author );

	if ( $author ) {
		$meta['creator'] = sanitize_username( $author->get( 'twitter' ) ?? '' );
	}

	return $meta;
}

/**
 * Output the twitter meta tags.
 */
function to_html() {
	$meta = get_meta_for_context( 'twitter' );
	to_meta_tags( $meta, 'twitter:' );
}
