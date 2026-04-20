<?php
/**
 * Plugin Name: CarCompare EG
 * Description: Aggregates used-car listings from OLX, Contact Cars, Hatla2ee, Sylndr, and YallaMotor Egypt into one searchable comparison widget. Use the shortcode [car_compare].
 * Version:     1.0.1
 * Author:      CarCompare
 * License:     MIT
 * Text Domain: carcompare-eg
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'CARCOMPARE_EG_VERSION', '1.0.1' );
define( 'CARCOMPARE_EG_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARCOMPARE_EG_URL', plugin_dir_url( __FILE__ ) );

require_once CARCOMPARE_EG_DIR . 'includes/helpers.php';
require_once CARCOMPARE_EG_DIR . 'includes/scrapers.php';

/**
 * Shortcode: [car_compare]
 */
function carcompare_eg_shortcode( $atts = array() ) {
	wp_enqueue_style( 'carcompare-eg', CARCOMPARE_EG_URL . 'assets/style.css', array(), CARCOMPARE_EG_VERSION );
	wp_enqueue_script( 'carcompare-eg', CARCOMPARE_EG_URL . 'assets/app.js', array(), CARCOMPARE_EG_VERSION, true );
	wp_localize_script( 'carcompare-eg', 'CarCompareEG', array(
		'endpoint' => esc_url_raw( rest_url( 'carcompare/v1/search' ) ),
		'sources'  => esc_url_raw( rest_url( 'carcompare/v1/sources' ) ),
		'nonce'    => wp_create_nonce( 'wp_rest' ),
	) );

	ob_start();
	?>
	<div class="cce-wrap">
		<header class="cce-header">
			<h2>CarCompare <span class="cce-tag">EG</span></h2>
			<p class="cce-sub">Search used cars across OLX, Contact Cars, Hatla2ee, Sylndr &amp; YallaMotor — all in one place.</p>
		</header>

		<form class="cce-search" id="cce-form">
			<input id="cce-q" type="text" placeholder="e.g. Toyota Corolla 2018" autocomplete="off" />
			<select id="cce-sort">
				<option value="price_asc">Price: Low → High</option>
				<option value="price_desc">Price: High → Low</option>
				<option value="year_desc">Year: Newest</option>
				<option value="source">Source</option>
			</select>
			<button type="submit">Compare prices</button>
		</form>

		<div class="cce-filters">
			<label>Min EGP <input id="cce-min-price" type="number" min="0" step="10000" /></label>
			<label>Max EGP <input id="cce-max-price" type="number" min="0" step="10000" /></label>
			<label>Min year <input id="cce-min-year" type="number" min="1990" max="2030" /></label>
			<div class="cce-sources" id="cce-sources"></div>
		</div>

		<div id="cce-stats" class="cce-stats cce-hidden"></div>
		<div id="cce-status" class="cce-status"></div>
		<div id="cce-results" class="cce-results"></div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'car_compare', 'carcompare_eg_shortcode' );

/**
 * REST API routes
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'carcompare/v1', '/sources', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			return rest_ensure_response( array( 'sources' => carcompare_eg_sources() ) );
		},
	) );

	register_rest_route( 'carcompare/v1', '/search', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q'        => array( 'type' => 'string', 'required' => true ),
			'sort'     => array( 'type' => 'string', 'default' => 'price_asc' ),
			'minPrice' => array( 'type' => 'integer' ),
			'maxPrice' => array( 'type' => 'integer' ),
			'minYear'  => array( 'type' => 'integer' ),
		),
		'callback' => 'carcompare_eg_rest_search',
	) );
} );

function carcompare_eg_rest_search( WP_REST_Request $req ) {
	$query    = sanitize_text_field( $req->get_param( 'q' ) );
	$sort     = sanitize_text_field( $req->get_param( 'sort' ) );
	$min_p    = $req->get_param( 'minPrice' );
	$max_p    = $req->get_param( 'maxPrice' );
	$min_y    = $req->get_param( 'minYear' );

	if ( $query === '' ) {
		return new WP_Error( 'cce_empty_query', 'Query is required', array( 'status' => 400 ) );
	}

	$cache_key = 'cce_' . md5( strtolower( $query ) );
	$all = get_transient( $cache_key );
	$errors = array();

	if ( false === $all ) {
		$all = array();
		foreach ( carcompare_eg_sources() as $source ) {
			$fn = 'carcompare_eg_scrape_' . $source['slug'];
			if ( function_exists( $fn ) ) {
				try {
					$items = call_user_func( $fn, $query );
					if ( is_array( $items ) ) { $all = array_merge( $all, $items ); }
				} catch ( Exception $e ) {
					$errors[] = array( 'source' => $source['name'], 'error' => $e->getMessage() );
				}
			}
		}
		set_transient( $cache_key, $all, 10 * MINUTE_IN_SECONDS );
	}

	$filtered = $all;
	if ( $min_p ) { $filtered = array_values( array_filter( $filtered, function ( $r ) use ( $min_p ) { return $r['price'] >= $min_p; } ) ); }
	if ( $max_p ) { $filtered = array_values( array_filter( $filtered, function ( $r ) use ( $max_p ) { return $r['price'] <= $max_p; } ) ); }
	if ( $min_y ) { $filtered = array_values( array_filter( $filtered, function ( $r ) use ( $min_y ) { return empty( $r['year'] ) || $r['year'] >= $min_y; } ) ); }

	usort( $filtered, function ( $a, $b ) use ( $sort ) {
		switch ( $sort ) {
			case 'price_desc': return $b['price'] - $a['price'];
			case 'year_desc':  return (int) ( ! empty( $b['year'] ) ? $b['year'] : 0 ) - (int) ( ! empty( $a['year'] ) ? $a['year'] : 0 );
			case 'source':     return strcmp( $a['source'], $b['source'] );
			default:           return $a['price'] - $b['price'];
		}
	} );

	$by_source = array();
	foreach ( $all as $it ) {
		$by_source[ $it['source'] ] = isset( $by_source[ $it['source'] ] ) ? $by_source[ $it['source'] ] + 1 : 1;
	}

	$stats = array( 'count' => count( $all ), 'min' => null, 'avg' => null, 'max' => null );
	if ( $all ) {
		$prices = array_map( function ( $x ) { return (int) $x['price']; }, $all );
		$stats['min'] = min( $prices );
		$stats['max'] = max( $prices );
		$stats['avg'] = (int) round( array_sum( $prices ) / count( $prices ) );
	}

	return rest_ensure_response( array(
		'query'    => $query,
		'stats'    => $stats,
		'bySource' => $by_source,
		'errors'   => $errors,
		'results'  => $filtered,
	) );
}
