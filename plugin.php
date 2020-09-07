<?php
/**
 * Plugin Name: Meta Tags
 * Description: Meta tags helper for WordPress including Opengraph, Twitter and JSON+LD support.
 * Version: 0.1.8
 * Author: Human Made
 * License: GPL-3.0
 */

namespace HM\MetaTags;

require_once __DIR__ . '/inc/namespace.php';

add_action( 'plugins_loaded', function () {

	/**
	 * Enable opengraph meta tag output.
	 *
	 * @param bool $enable If true outputs opengraph meta tags.
	 */
	$enable_opengraph = apply_filters( 'hm.metatags.opengraph', true );
	if ( $enable_opengraph ) {
		require_once __DIR__ . '/inc/opengraph-namespace.php';
		Opengraph\bootstrap();
	}

	/**
	 * Enable twitter meta tag output.
	 *
	 * @param bool $enable If true outputs twitter meta tags.
	 */
	$enable_twitter = apply_filters( 'hm.metatags.twitter', true );
	if ( $enable_twitter ) {
		require_once __DIR__ . '/inc/twitter-namespace.php';
		Twitter\bootstrap();
	}

	/**
	 * Enable JSON+LD script tag output.
	 *
	 * @param bool $enable If true outputs JSON+LD script tags.
	 */
	$enable_json_ld = apply_filters( 'hm.metatags.json_ld', true );
	if ( $enable_json_ld ) {
		require_once __DIR__ . '/inc/json-ld-namespace.php';
		JSONLD\bootstrap();
	}

}, 11 );
