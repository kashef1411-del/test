<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function carcompare_eg_scrape_olx( $query ) {
	$base = 'https://www.olx.com.eg';
	$url  = $base . '/en/vehicles/cars-for-sale/q-' . rawurlencode( $query ) . '/';
	$html = carcompare_eg_fetch( $url );
	$dom  = carcompare_eg_load_dom( $html );
	$xp   = new DOMXPath( $dom );
	$out  = array();

	$nodes = $xp->query( '//*[@data-aut-id="itemBox"]' );
	if ( $nodes ) {
		foreach ( $nodes as $el ) {
			$title = carcompare_eg_xp_text( $xp, $el, './/*[@data-aut-id="itemTitle"]' );
			$price = carcompare_eg_parse_price( carcompare_eg_xp_text( $xp, $el, './/*[@data-aut-id="itemPrice"]' ) );
			$href  = carcompare_eg_xp_attr( $xp, $el, './/a', 'href' );
			$loc   = carcompare_eg_xp_text( $xp, $el, './/*[@data-aut-id="item-location"]' );
			if ( $title && $price ) {
				$out[] = array(
					'source'   => 'OLX Egypt',
					'title'    => $title,
					'price'    => $price,
					'currency' => 'EGP',
					'year'     => carcompare_eg_parse_year( $title ),
					'location' => $loc ?: null,
					'url'      => carcompare_eg_abs_url( $base, $href ),
				);
			}
		}
	}

	// JSON-LD fallback
	if ( empty( $out ) ) {
		$scripts = $xp->query( '//script[@type="application/ld+json"]' );
		foreach ( $scripts as $s ) {
			$data = json_decode( $s->textContent, true );
			if ( ! is_array( $data ) ) { continue; }
			$items = isset( $data[0] ) ? $data : ( isset( $data['@graph'] ) ? $data['@graph'] : array( $data ) );
			foreach ( $items as $it ) {
				if ( ! is_array( $it ) ) { continue; }
				$type = isset( $it['@type'] ) ? $it['@type'] : '';
				if ( $type === 'Product' || ! empty( $it['offers'] ) ) {
					$title = isset( $it['name'] ) ? trim( (string) $it['name'] ) : '';
					$price = null;
					if ( ! empty( $it['offers'] ) ) {
						$of = $it['offers'];
						$price = carcompare_eg_parse_price( isset( $of['price'] ) ? $of['price'] : ( isset( $of['lowPrice'] ) ? $of['lowPrice'] : null ) );
					}
					if ( $title && $price ) {
						$out[] = array(
							'source'   => 'OLX Egypt',
							'title'    => $title,
							'price'    => $price,
							'currency' => 'EGP',
							'year'     => carcompare_eg_parse_year( $title ),
							'location' => null,
							'url'      => isset( $it['url'] ) ? $it['url'] : null,
						);
					}
				}
			}
		}
	}

	return array_values( array_filter( $out, function ( $r ) use ( $query ) { return carcompare_eg_matches_query( $r['title'], $query ); } ) );
}

function carcompare_eg_scrape_contactcars( $query ) {
	$base = 'https://www.contactcars.com';
	$url  = $base . '/en/used-cars/search?query=' . rawurlencode( $query );
	$html = carcompare_eg_fetch( $url );
	$dom  = carcompare_eg_load_dom( $html );
	$xp   = new DOMXPath( $dom );
	$out  = array();

	$nodes = $xp->query( "//*[contains(@class,'car-item') or contains(@class,'car-card') or contains(@class,'listing-item') or contains(@class,'CarCard')]" );
	if ( $nodes ) {
		foreach ( $nodes as $el ) {
			$title = carcompare_eg_xp_text( $xp, $el, ".//h2 | .//h3 | .//*[contains(@class,'title')]" );
			$price = carcompare_eg_parse_price( carcompare_eg_xp_text( $xp, $el, ".//*[contains(@class,'price') or contains(@class,'Price')]" ) );
			$href  = carcompare_eg_xp_attr( $xp, $el, './/a', 'href' );
			if ( $title && $price ) {
				$out[] = array(
					'source'   => 'Contact Cars',
					'title'    => $title,
					'price'    => $price,
					'currency' => 'EGP',
					'year'     => carcompare_eg_parse_year( $title ),
					'location' => null,
					'url'      => carcompare_eg_abs_url( $base, $href ),
				);
			}
		}
	}
	return array_values( array_filter( $out, function ( $r ) use ( $query ) { return carcompare_eg_matches_query( $r['title'], $query ); } ) );
}

function carcompare_eg_scrape_hatla2ee( $query ) {
	$base = 'https://eg.hatla2ee.com';
	$url  = $base . '/en/car?keyword=' . rawurlencode( $query );
	$html = carcompare_eg_fetch( $url );
	$dom  = carcompare_eg_load_dom( $html );
	$xp   = new DOMXPath( $dom );
	$out  = array();

	$nodes = $xp->query( "//*[contains(@class,'newCarListUnit') or contains(@class,'carListUnit') or contains(@class,'usedCarListUnit')]" );
	if ( $nodes ) {
		foreach ( $nodes as $el ) {
			$title = carcompare_eg_xp_text( $xp, $el, ".//*[contains(@class,'newCarListUnit_header')]//a | .//*[contains(@class,'newCarListUnit_title')] | .//h3//a | .//h2//a" );
			if ( ! $title ) { $title = carcompare_eg_xp_attr( $xp, $el, './/a', 'title' ); }
			$price = carcompare_eg_parse_price( carcompare_eg_xp_text( $xp, $el, ".//*[contains(@class,'main_price') or contains(@class,'newCarListUnit_price') or contains(@class,'price')]" ) );
			$href  = carcompare_eg_xp_attr( $xp, $el, './/a', 'href' );
			$loc   = carcompare_eg_xp_text( $xp, $el, ".//*[contains(@class,'newCarListUnit_address') or contains(@class,'location')]" );
			if ( $title && $price ) {
				$out[] = array(
					'source'   => 'Hatla2ee',
					'title'    => $title,
					'price'    => $price,
					'currency' => 'EGP',
					'year'     => carcompare_eg_parse_year( $title ),
					'location' => $loc ?: null,
					'url'      => carcompare_eg_abs_url( $base, $href ),
				);
			}
		}
	}
	return array_values( array_filter( $out, function ( $r ) use ( $query ) { return carcompare_eg_matches_query( $r['title'], $query ); } ) );
}

function carcompare_eg_scrape_sylndr( $query ) {
	$base = 'https://sylndr.com';
	$url  = $base . '/en/buy-used-cars';
	$html = carcompare_eg_fetch( $url );
	$dom  = carcompare_eg_load_dom( $html );
	$xp   = new DOMXPath( $dom );
	$out  = array();

	$next = $xp->query( '//script[@id="__NEXT_DATA__"]' );
	if ( $next && $next->length ) {
		$data = json_decode( $next->item( 0 )->textContent, true );
		if ( is_array( $data ) ) {
			$stack = array( $data );
			$depth = 0;
			while ( $stack && $depth < 50000 ) {
				$node = array_pop( $stack );
				$depth++;
				if ( ! is_array( $node ) ) { continue; }
				$has_price = array_key_exists( 'price', $node );
				$has_title = array_key_exists( 'title', $node ) || array_key_exists( 'make', $node ) || array_key_exists( 'name', $node );
				if ( $has_price && $has_title ) {
					$title = '';
					if ( ! empty( $node['title'] ) ) {
						$title = (string) $node['title'];
					} else {
						$parts = array();
						foreach ( array( 'year', 'make', 'model', 'trim' ) as $k ) {
							if ( ! empty( $node[ $k ] ) ) { $parts[] = (string) $node[ $k ]; }
						}
						$title = implode( ' ', $parts );
					}
					$price_raw = $node['price'];
					if ( is_array( $price_raw ) ) {
						$price_raw = isset( $price_raw['amount'] ) ? $price_raw['amount'] : ( isset( $price_raw['value'] ) ? $price_raw['value'] : null );
					}
					$price = carcompare_eg_parse_price( $price_raw );
					if ( $title && $price ) {
						$url_item = $base;
						if ( ! empty( $node['url'] ) ) { $url_item = carcompare_eg_abs_url( $base, $node['url'] ); }
						elseif ( ! empty( $node['slug'] ) ) { $url_item = $base . '/en/buy-used-cars/' . $node['slug']; }
						$out[] = array(
							'source'   => 'Sylndr',
							'title'    => trim( $title ),
							'price'    => $price,
							'currency' => 'EGP',
							'year'     => ! empty( $node['year'] ) ? (int) $node['year'] : carcompare_eg_parse_year( $title ),
							'location' => ! empty( $node['city'] ) ? $node['city'] : ( ! empty( $node['location'] ) ? $node['location'] : null ),
							'url'      => $url_item,
						);
					}
				}
				foreach ( $node as $v ) { if ( is_array( $v ) ) { $stack[] = $v; } }
			}
		}
	}

	return array_values( array_filter( $out, function ( $r ) use ( $query ) { return carcompare_eg_matches_query( $r['title'], $query ); } ) );
}

function carcompare_eg_scrape_yallamotor( $query ) {
	$base = 'https://egypt.yallamotor.com';
	$url  = $base . '/used-cars/search?q=' . rawurlencode( $query );
	$html = carcompare_eg_fetch( $url );
	$dom  = carcompare_eg_load_dom( $html );
	$xp   = new DOMXPath( $dom );
	$out  = array();

	$nodes = $xp->query( "//*[contains(@class,'used-car-card') or contains(@class,'car-card') or contains(@class,'listing-card') or self::article]" );
	if ( $nodes ) {
		foreach ( $nodes as $el ) {
			$title = carcompare_eg_xp_text( $xp, $el, ".//h2 | .//h3 | .//*[contains(@class,'title')]" );
			if ( ! $title ) { $title = carcompare_eg_xp_attr( $xp, $el, './/a', 'title' ); }
			$price = carcompare_eg_parse_price( carcompare_eg_xp_text( $xp, $el, ".//*[contains(@class,'price') or contains(@class,'Price')]" ) );
			$href  = carcompare_eg_xp_attr( $xp, $el, './/a', 'href' );
			if ( $title && $price ) {
				$out[] = array(
					'source'   => 'YallaMotor Egypt',
					'title'    => $title,
					'price'    => $price,
					'currency' => 'EGP',
					'year'     => carcompare_eg_parse_year( $title ),
					'location' => null,
					'url'      => carcompare_eg_abs_url( $base, $href ),
				);
			}
		}
	}
	return array_values( array_filter( $out, function ( $r ) use ( $query ) { return carcompare_eg_matches_query( $r['title'], $query ); } ) );
}
