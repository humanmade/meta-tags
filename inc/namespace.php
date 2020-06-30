<?php
/**
 * HM Meta tags functions.
 *
 * @package hm-metatags
 */

namespace HM\MetaTags;

use WP_Post;

/**
 * Get the current URL.
 *
 * @param bool $query_string Whether to append the current query string.
 * @return string
 */
function get_current_url( bool $query_string = false ) : string {
	$url = sprintf(
		'http://%s%s%s',
		$_SERVER['HTTP_HOST'],
		$_SERVER['REQUEST_URI'],
		isset( $_SERVER['QUERY_STRING'] ) && $query_string ? '?' . $_SERVER['QUERY_STRING'] : ''
	);
	return set_url_scheme( $url );
}

/**
 * Builds up an array of contextual and global information used
 * by functions to output
 *
 * @param string $type Type of metadata to retrieve - used in filter names.
 * @return array
 */
function get_meta_for_context( string $type = 'default' ) : array {
	global $page, $pages;

	if ( ! did_action( 'wp' ) ) {
		trigger_error( 'HM\MetaTags\get_contextual_data() was called before the "wp" action', E_USER_WARNING );
		return [];
	}

	// Meta data array.
	$meta = [];

	// Queried data.
	$object = get_queried_object();
	$object_id = get_queried_object_id();

	// Contextual values.
	$context = [
		'object' => $object,
		'object_id' => $object_id,
		'locale' => get_locale(),
		'title' => wp_title( '|', false, 'right' ),
		'description' => '',
		'image' => get_fallback_image(),
		'url' => get_current_url(),
	];

	// Set logo as default image if supported.
	if ( current_theme_supports( 'custom-logo' ) ) {
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$context['logo'] = wp_get_attachment_image_url( $logo_id, 'full' );
		}
	}

	// Set a fallback image if available.


	// Front page.
	if ( is_front_page() ) {
		$context['title'] = get_bloginfo( 'name' );
		$context['description'] = get_bloginfo( 'description' );
		$context['url'] = get_home_url();
		$context['image'] = $context['logo'] ?? $context['image'];

		/**
		 * Filter meta data for the sites front page.
		 *
		 * @param array $meta The key value pairs for output to HTML.
		 * @param array $context Information about the current context.
		 */
		$meta = apply_filters( "hm.metatags.context.$type.front_page", [], $context );
	}

	// Singular post, any type.
	if ( is_singular() ) {
		$context['title'] = get_the_title( $object_id );
		$context['description'] = get_the_excerpt( $object );
		$context['url'] = get_the_permalink( $object );
		$context['author'] = get_the_author_meta( 'display_name' );

		if ( has_post_thumbnail( $object ) ) {
			$context['image'] = get_the_post_thumbnail_url( $object, 'full' );
			$context['image_id'] = get_post_thumbnail_id( $object );
		}

		$context['taxonomies'] = [];

		foreach ( get_object_taxonomies( $object ) as $taxonomy ) {
			if ( is_object_in_taxonomy( $object->post_type, $taxonomy ) ) {
				$context[ $taxonomy ] = [];
				$context['taxonomies'][ $taxonomy ] = [];
				$terms = get_the_terms( $object, $taxonomy );
				if ( is_array( $terms ) ) {
					$terms = wp_list_pluck( $terms, 'name' );
					$context[ $taxonomy ] = $terms;
					$context['taxonomies'][ $taxonomy ] = $terms;
				}
			}
		}

		// Attachments.
		if ( is_attachment() ) {
			$context['mime_type'] = get_post_mime_type( $object_id );
			$context['image'] = wp_get_attachment_image_url( $object_id, 'full' );
			$context['url'] = wp_get_attachment_url( $object_id );
			$context['type'] = 'media';
		}

		/**
		 * Filter meta data for posts.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context Current contextual data.
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.singular", [], $context );

		/**
		 * Filter meta data for a specific post type.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context Current contextual data.
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.singular.{$object->post_type}", $meta, $context );
	}

	// Home / blog.
	if ( is_home() && ! is_front_page() ) {
		$page = get_post( get_option( 'page_for_posts' ) );
		$context['object'] = get_post_type_object( 'post' );
		$context['title'] = get_the_title( $page );
		$context['description'] = get_the_excerpt( $page );
		$context['url'] = get_the_permalink( $page );

		/**
		 * Filter contextual data for post type archive.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.blog", [], $context );
	}

	// Taxonomy term.
	if ( is_tax() || is_tag() || is_category() ) {
		$context['title'] = get_the_archive_title();
		$context['description'] = get_the_archive_description();
		$context['url'] = get_term_link( $object, $object->taxonomy );

		/**
		 * Filter contextual data for terms.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.taxonomy", [], $context );
	}

	// Post type archive.
	if ( is_post_type_archive() ) {
		$context['title'] = get_the_archive_title();
		$context['description'] = get_the_archive_description();
		$context['url'] = get_post_type_archive_link( $object->name );

		/**
		 * Filter contextual data for post type archive.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.post_type", [], $context );
	}

	// Author.
	if ( is_author() ) {
		$context['title'] = get_the_archive_title();
		$context['description'] = get_the_archive_description();
		$context['url'] = get_author_posts_url( $object_id );
		$context['image'] = get_avatar_url( $object_id, [
			'size' => 1028
		] );

		/**
		 * Filter contextual data for user.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.author", [], $context );
	}

	// Date.
	if ( is_date() ) {
		$context['context'] = 'date';
		$context['title'] = get_the_archive_title();
		$context['description'] = get_the_archive_description();

		/**
		 * Filter contextual data for date archives.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.date", [], $context );
	}

	// Search.
	if ( is_search() ) {
		$context['search_term'] = get_search_query();

		/**
		 * Filter contextual data for terms.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.search", [], $context );
	}

	// 404.
	if ( is_404() ) {
		$context['context'] = '404';

		/**
		 * Filter contextual data for terms.
		 *
		 * @param array $meta The meta data array to populate.
		 * @param array $context
		 */
		$meta = apply_filters( "hm.metatags.context.{$type}.404", [], $context );
	}

	// Sanitize the meta data.
	$meta = array_map( __NAMESPACE__ . '\\sanitize_data', $meta );

	/**
	 * Filter final meta data.
	 *
	 * @param array $meta The meta data array.
	 * @param array $context
	 */
	$meta = apply_filters( "hm.metatags.context.{$type}", $meta, $context );
	$meta = array_filter( $meta );

	return $meta;
}

/**
 * Sanitize the data for output as meta tags / JSON.
 *
 * @param mixed $value Value to sanitize.
 * @return mixed
 */
function sanitize_data( $value ) {
	if ( is_array( $value ) ) {
		$value = array_map( __NAMESPACE__ . '\\sanitize_data', $value );
	}

	if ( is_string( $value ) ) {
		$value = wp_strip_all_tags( $value );
	}

	return $value;
}

/**
 * Shim to get the excerpt outside of the loop
 *
 * @param WP_Post $post
 * @return void
 */
function get_the_excerpt( WP_Post $post ) {
	ob_start();
	setup_postdata( $post );
	the_excerpt();
	$excerpt = ob_get_clean();
	$excerpt = wp_strip_all_tags( $excerpt );
	wp_reset_postdata();

	return $excerpt;
}

/**
 * Output an associative array as HTML meta tags.
 *
 * @param array $meta The meta data to display.
 * @param string $prefix A prefix for the meta name value eg. 'og:'
 * @param string $name_attribute The attribute name for the meta key.
 * @param string $value_attribute The attribute name for the meta value.
 * @return void
 */
function to_meta_tags( array $meta, string $prefix = '', string $name_attribute = 'name', string $value_attribute = 'content' ) {
	$output = array_reduce( array_keys( $meta ), function ( $carry, $key ) use ( $meta, $prefix, $name_attribute, $value_attribute ) {
		if ( empty( $meta[ $key ] ) ) {
			return $carry;
		}

		if ( ! is_array( $meta[ $key ] ) ) {
			$meta[ $key ] = [ $meta[ $key ] ];
		}

		foreach( $meta[ $key ] as $value ) {
			$carry = sprintf(
				"%s<meta %s=\"%s%s\" %s=\"%s\" />\n",
				$carry,
				sanitize_key( $name_attribute ),
				esc_attr( $prefix ),
				esc_attr( $key ),
				sanitize_key( $value_attribute ),
				esc_attr( wp_strip_all_tags( $value ) )
			);
		}

		return $carry;
	}, "\n" );

	echo $output;
}

/**
 * Output an array of metadata as a script tag containing JSON.
 *
 * @param array $meta The metadata to display.
 * @param string $type The script type attribute.
 * @return void
 */
function to_script_tag( array $meta, string $type = 'application/ld+json' ) {
	if ( empty( $meta ) ) {
		return;
	}

	$output = sprintf(
		"\n<script type=\"%s\">\n%s\n</script>\n\n",
		esc_attr( $type ),
		wp_json_encode( $meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
	);

	echo $output;
}

/**
 * Return social URLs for the site. Used by JSON+LD and others.
 *
 * @return array
 */
function get_social_urls() : array {
	/**
	 * Filters a list of related social platform URLs for this site.
	 *
	 * Available keys are:
	 *
	 * - google
	 * - facebook
	 * - twitter
	 * - instagram
	 * - youtube
	 * - linkedin
	 * - myspace
	 * - pinterest
	 * - soundcloud
	 * - tumblr
	 *
	 * @param array $social_urls
	 */
	$social_urls = apply_filters( 'hm.metatags.social_urls', [
		'google' => '',
		'facebook' => '',
		'twitter' => '',
		'instagram' => '',
		'youtube' => '',
		'linkedin' => '',
		'myspace' => '',
		'pinterest' => '',
		'soundcloud' => '',
		'tumblr' => '',
	] );

	return $social_urls ?? [];
}

/**
 * Get the fallback / default image path.
 *
 * @return string
 */
function get_fallback_image() : string {
	/**
	 * Filters a list of related social platform URLs for this site.
	 *
	 * @param string $url Relative URL path.
	 */
	$fallback_image = apply_filters( 'hm.metatags.fallback_image', '' );

	return $fallback_image ?? '';
}
