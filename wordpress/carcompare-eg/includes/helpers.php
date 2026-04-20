<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function carcompare_eg_sources() {
	return array(
		array( 'slug' => 'olx',          'name' => 'OLX Egypt',        'home' => 'https://www.olx.com.eg' ),
		array( 'slug' => 'contactcars',  'name' => 'Contact Cars',     'home' => 'https://www.contactcars.com' ),
		array( 'slug' => 'hatla2ee',     'name' => 'Hatla2ee',         'home' => 'https://eg.hatla2ee.com' ),
		array( 'slug' => 'sylndr',       'name' => 'Sylndr',           'home' => 'https://sylndr.com' ),
		array( 'slug' => 'yallamotor',   'name' => 'YallaMotor Egypt', 'home' => 'https://egypt.yallamotor.com' ),
	);
}

function carcompare_eg_search_url( $slug, $query ) {
	$q = rawurlencode( $query );
	switch ( $slug ) {
		case 'olx':         return 'https://www.olx.com.eg/en/vehicles/cars-for-sale/q-' . $q . '/';
		case 'contactcars': return 'https://www.contactcars.com/en/used-cars/search?query=' . $q;
		case 'hatla2ee':    return 'https://eg.hatla2ee.com/en/car?keyword=' . $q;
		case 'sylndr':      return 'https://sylndr.com/en/buy-used-cars?search=' . $q;
		case 'yallamotor':  return 'https://egypt.yallamotor.com/used-cars/search?q=' . $q;
	}
	return '';
}

function carcompare_eg_fetch( $url ) {
	$api_key = trim( (string) get_option( 'carcompare_eg_scraperapi_key', '' ) );
	$render  = (bool) get_option( 'carcompare_eg_render_js', false );

	if ( $api_key ) {
		$proxy = 'https://api.scraperapi.com/?api_key=' . rawurlencode( $api_key ) . '&url=' . rawurlencode( $url );
		if ( $render ) { $proxy .= '&render=true'; }
		$target = $proxy;
		$timeout = 60;
	} else {
		$target = $url;
		$timeout = 15;
	}

	$res = wp_remote_get( $target, array(
		'timeout'     => $timeout,
		'redirection' => 5,
		'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
		'headers'     => array(
			'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
		),
	) );
	if ( is_wp_error( $res ) ) { throw new Exception( $res->get_error_message() ); }
	$code = wp_remote_retrieve_response_code( $res );
	if ( $code < 200 || $code >= 400 ) { throw new Exception( 'HTTP ' . $code . ( $api_key ? ' via ScraperAPI' : '' ) ); }
	return wp_remote_retrieve_body( $res );
}

function carcompare_eg_load_dom( $html ) {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	return $dom;
}

function carcompare_eg_parse_price( $text ) {
	if ( $text === null || $text === '' ) { return null; }
	$cleaned = preg_replace( '/[,\s\x{066C}]/u', '', (string) $text );
	if ( preg_match( '/(\d{4,10})/', $cleaned, $m ) ) {
		$n = (int) $m[1];
		return $n >= 10000 ? $n : null;
	}
	return null;
}

function carcompare_eg_parse_year( $text ) {
	if ( $text && preg_match( '/\b(19|20)\d{2}\b/', (string) $text, $m ) ) { return (int) $m[0]; }
	return null;
}

function carcompare_eg_abs_url( $base, $href ) {
	if ( ! $href ) { return null; }
	if ( preg_match( '#^https?://#i', $href ) ) { return $href; }
	if ( strpos( $href, '//' ) === 0 ) { return 'https:' . $href; }
	$b = parse_url( $base );
	if ( ! $b ) { return $href; }
	$scheme = isset( $b['scheme'] ) ? $b['scheme'] : 'https';
	$host   = isset( $b['host'] ) ? $b['host'] : '';
	if ( strpos( $href, '/' ) === 0 ) { return $scheme . '://' . $host . $href; }
	return $scheme . '://' . $host . '/' . ltrim( $href, '/' );
}

function carcompare_eg_matches_query( $title, $query ) {
	if ( ! $query ) { return true; }
	$t = strtolower( (string) $title );
	foreach ( preg_split( '/\s+/', trim( strtolower( $query ) ) ) as $tok ) {
		if ( $tok !== '' && strpos( $t, $tok ) === false ) { return false; }
	}
	return true;
}

function carcompare_eg_xp_text( DOMXPath $xp, DOMNode $ctx, $expr ) {
	$nodes = $xp->query( $expr, $ctx );
	if ( $nodes && $nodes->length ) {
		return trim( preg_replace( '/\s+/', ' ', $nodes->item( 0 )->textContent ) );
	}
	return '';
}

function carcompare_eg_xp_attr( DOMXPath $xp, DOMNode $ctx, $expr, $attr ) {
	$nodes = $xp->query( $expr, $ctx );
	if ( $nodes && $nodes->length ) {
		$n = $nodes->item( 0 );
		if ( $n instanceof DOMElement ) { return $n->getAttribute( $attr ); }
	}
	return '';
}
