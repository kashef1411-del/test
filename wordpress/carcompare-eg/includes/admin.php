<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
	add_options_page(
		'CarCompare EG',
		'CarCompare EG',
		'manage_options',
		'carcompare-eg',
		'carcompare_eg_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'carcompare_eg', 'carcompare_eg_scraperapi_key', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );
	register_setting( 'carcompare_eg', 'carcompare_eg_render_js', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	) );
} );

function carcompare_eg_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	?>
	<div class="wrap">
		<h1>CarCompare EG — Settings</h1>
		<p><strong>You don't have to configure anything to use the plugin.</strong> By default the widget tries to scrape each source directly, shows any listings it can pull, and always displays <em>"Open on [site]"</em> deep-link buttons so visitors can jump straight to the same search on each site — even when scraping is blocked.</p>
		<p>Optional: if you want richer inline results, sign up at <a href="https://www.scraperapi.com/" target="_blank" rel="noopener">ScraperAPI</a> (free tier: 1,000 requests/month) or a similar provider and paste the key below. Leave it blank to stay fully free.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'carcompare_eg' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="carcompare_eg_scraperapi_key">ScraperAPI key</label></th>
					<td>
						<input name="carcompare_eg_scraperapi_key" id="carcompare_eg_scraperapi_key" type="text" class="regular-text" value="<?php echo esc_attr( get_option( 'carcompare_eg_scraperapi_key', '' ) ); ?>" />
						<p class="description">Sign up at scraperapi.com → copy your API key here. Leave blank for direct fetch (will likely be blocked).</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Render JavaScript</th>
					<td>
						<label>
							<input name="carcompare_eg_render_js" type="checkbox" value="1" <?php checked( (bool) get_option( 'carcompare_eg_render_js', false ) ); ?> />
							Ask ScraperAPI to render JS before returning HTML (needed for Contact Cars / YallaMotor; uses more credits)
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2>Shortcode</h2>
		<p>Add <code>[car_compare]</code> to any page or post. Results are cached for 10 minutes per query (via transients).</p>

		<h2>Clear cache</h2>
		<form method="post">
			<?php wp_nonce_field( 'carcompare_eg_clear' ); ?>
			<input type="hidden" name="carcompare_eg_action" value="clear_cache" />
			<?php submit_button( 'Clear cached results', 'secondary', 'clear_cache', false ); ?>
		</form>
	</div>
	<?php

	if ( isset( $_POST['carcompare_eg_action'] ) && $_POST['carcompare_eg_action'] === 'clear_cache' && check_admin_referer( 'carcompare_eg_clear' ) ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cce_%' OR option_name LIKE '_transient_timeout_cce_%'" );
		echo '<div class="notice notice-success is-dismissible"><p>Cache cleared.</p></div>';
	}
}
