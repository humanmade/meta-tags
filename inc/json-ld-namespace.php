<?php
/**
 * JSON LD Metadata functions.
 *
 * @package hm-metatags
 */

namespace HM\MetaTags\JSONLD;

use WP_User;
use function HM\MetaTags\get_current_url;
use function HM\MetaTags\get_social_urls;
use function HM\MetaTags\get_meta_for_context;
use function HM\MetaTags\to_script_tag;

function bootstrap() {
	add_filter( 'hm.metatags.context.json_ld.archive', __NAMESPACE__ . '\\archive', 10, 2 );
	add_filter( 'hm.metatags.context.json_ld.author', __NAMESPACE__ . '\\author', 10, 2 );
	add_filter( 'hm.metatags.context.json_ld.blog', __NAMESPACE__ . '\\archive', 10, 2 );
	add_filter( 'hm.metatags.context.json_ld.front_page', __NAMESPACE__ . '\\front_page', 10, 2 );
	add_filter( 'hm.metatags.context.json_ld.search', __NAMESPACE__ . '\\search', 10, 2 );
	add_filter( 'hm.metatags.context.json_ld.singular', __NAMESPACE__ . '\\singular', 10, 2 );
	add_filter( 'hm.metatags.context.json_ld.taxonomy', __NAMESPACE__ . '\\archive', 10, 2 );
	add_action( 'wp_head', __NAMESPACE__ . '\\to_html' );
}

/**
 * Get a JSON LD array for a user.
 *
 * @param WP_User $user
 * @return array
 */
function get_person( WP_User $user ) : array {
	$meta = [];
	$meta['@type'] = 'Person';
	$meta['@id'] = get_author_posts_url( $user->ID );
	$meta['name'] = $user->get( 'display_name' );
	$meta['url'] = $meta['@id'];
	$meta['description'] = $user->get( 'description' );

	// Extract URL values from contact methods.
	$contact_methods = wp_get_user_contact_methods( $user );
	$contact_methods = array_map( function ( $key ) use ( $user ) {
		return $user->get( $key );
	}, array_keys( $contact_methods ) );
	$contact_methods = array_filter( $contact_methods, function ( $method ) {
		return filter_var( $method, FILTER_VALIDATE_URL );
	} );
	$meta['sameAs'] = $contact_methods;

	$meta = array_filter( $meta );

	return $meta;
}

/**
 * Get the knowledge graph for the site.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function front_page( array $meta, array $context ) : array {
	$meta['@type'] = 'Organization';
	$meta['@id'] = '#organization';
	$meta['name'] = get_bloginfo( 'name' );
	$meta['description'] = get_bloginfo( 'description' );
	$meta['url'] = get_home_url();
	$meta['logo'] = $context['logo'] ?? false;
	$meta['sameAs'] = array_filter( array_values( get_social_urls() ) );

	$meta = array_filter( $meta );

	return $meta;
}

/**
 * Get the singular page JSON+LD.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function singular( array $meta, array $context ) : array {
	if ( is_post_type_hierarchical( $context['object']->post_type ) ?? true ) {
		$meta['@type'] = 'WebPage';
	} else {
		$meta['@type'] = 'Article';
	}

	$meta['headline'] = $context['title'];
	$meta['datePublished'] = get_the_date( 'c', $context['object'] );
	$meta['dateModified'] = get_the_modified_date( 'c', $context['object'] );
	$meta['mainEntityOfPage'] = get_the_permalink( $context['object_id'] );
	
	// Post author is only set for post types that support it.
	if ( post_type_supports( $context['object']->post_type, 'author' ) && ! empty( $context['object']->post_author ) ) {
		$author = get_user_by( 'id', $context['object']->post_author );

		if ( $author instanceof WP_User ) {
			$meta['author'] = [
				get_person( $author ),
			];
		}
	}
	
	$meta['keywords'] = [];
	foreach ( get_post_taxonomies( $context['object'] ) as $taxonomy ) {
		if ( $context['taxonomies'][ $taxonomy ] ?? false ) {
			$meta['keywords'] = array_merge( $context['taxonomies'][ $taxonomy ], $meta['keywords'] );
			$meta['keywords'] = array_unique( $meta['keywords'] );
		}
	}
	$meta['keywords'] = implode( ', ', $meta['keywords'] );
	$meta['image'] = $context['image'];
	$meta['url'] = $context['url'];
	$meta['publisher'] = [
		front_page( [], $context ),
	];

	return $meta;
}

/**
 * Get archive / collection page meta data.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function archive( array $meta, array $context ) : array {
	$meta['@type'] = 'CollectionPage';
	$meta['@id'] = $context['url'];
	$meta['headline'] = $context['title'];
	$meta['description'] = $context['description'] ?? '';
	$meta['url'] = $context['url'];

	return $meta;
}

/**
 * Get the author meta data.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function author( array $meta, array $context ) : array {
	$meta = get_person( $context['object'] );
	return $meta;
}

/**
 * Get search page meta data.
 *
 * @param array $meta
 * @param array $context
 * @return array
 */
function search( array $meta, array $context ) : array {
	$meta['@type'] = 'SearchResultsPage';
	$meta['@id'] = get_current_url();
	$meta['headline'] = $context['title'] ?? __( 'Search' );
	$meta['url'] = get_current_url( true );

	return $meta;
}

/**
 * Output the JSON LD meta data.
 */
function to_html() {
	$meta = get_meta_for_context( 'json_ld' );
	$meta = array_merge( [ '@context' => 'https://schema.org' ], $meta );
	to_script_tag( $meta );
}
