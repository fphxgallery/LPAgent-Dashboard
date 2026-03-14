<?php
/**
 * Plugin Name: LP Metrics
 * Plugin URI:  https://lpagent.io
 * Description: Displays liquidity pool position metrics and 1-month PnL for a Solana wallet via the LPAgent API.
 * Version:     1.0.0
 * Author:      @fPHXGallery
 * License:     GPL-2.0-or-later
 * Text Domain: lp-metrics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LPM_VERSION',    '1.0.0' );
define( 'LPM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LPM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LPM_TEXT_DOMAIN', 'lp-metrics' );

final class LP_Metrics {

	private static ?LP_Metrics $instance = null;

	/** Whether the shortcode was rendered on this page load (controls asset enqueue). */
	private bool $shortcode_used = false;

	public static function instance(): LP_Metrics {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies(): void {
		require_once LPM_PLUGIN_DIR . 'includes/class-api-client.php';
		require_once LPM_PLUGIN_DIR . 'admin/class-admin-settings.php';
	}

	private function init_hooks(): void {
		add_action( 'init', [ $this, 'register_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );

		if ( is_admin() ) {
			new LPM_Admin_Settings();
		}
	}

	public function register_shortcode(): void {
		add_shortcode( 'lp_metrics', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Shortcode: [lp_metrics wallet="ADDR" protocol="meteora"]
	 */
	public function render_shortcode( array $atts = [] ): string {
		$atts = shortcode_atts( [
			'wallet'   => '',
			'protocol' => get_option( 'lpm_default_protocol', 'meteora' ),
		], $atts, 'lp_metrics' );

		$wallet   = sanitize_text_field( $atts['wallet'] );
		$protocol = sanitize_text_field( $atts['protocol'] );

		if ( empty( $wallet ) ) {
			return '<div class="lpm-error lpm-glass">' . esc_html__( 'LP Metrics: wallet address is required.', LPM_TEXT_DOMAIN ) . '</div>';
		}

		// Validate Solana base58 address (32–44 chars).
		if ( ! preg_match( '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet ) ) {
			return '<div class="lpm-error lpm-glass">' . esc_html__( 'LP Metrics: invalid wallet address.', LPM_TEXT_DOMAIN ) . '</div>';
		}

		$api       = new LPM_API_Client();
		$overview  = $api->get_overview( $wallet, $protocol );
		$revenue   = $api->get_revenue( $wallet, $protocol );
		$positions = $api->get_positions( $wallet );

		$this->shortcode_used = true;

		ob_start();

		echo '<div class="lpm-wrap">';

		if ( is_wp_error( $overview ) ) {
			echo '<div class="lpm-error lpm-glass">' . esc_html( $overview->get_error_message() ) . '</div>';
		} else {
			include LPM_PLUGIN_DIR . 'templates/metrics-card.php';
		}

		echo '<div class="lpm-combined lpm-glass">';

		if ( is_wp_error( $revenue ) ) {
			echo '<div class="lpm-error">' . esc_html( $revenue->get_error_message() ) . '</div>';
		} else {
			include LPM_PLUGIN_DIR . 'templates/revenue-chart.php';
		}

		echo '<hr class="lpm-divider" />';

		if ( is_wp_error( $positions ) ) {
			echo '<div class="lpm-error">' . esc_html( $positions->get_error_message() ) . '</div>';
		} else {
			include LPM_PLUGIN_DIR . 'templates/positions-table.php';
		}

		if ( ! is_wp_error( $revenue ) && ! empty( $revenue ) ) {
			echo '<hr class="lpm-divider" />';
			include LPM_PLUGIN_DIR . 'templates/revenue-table.php';
		}

		echo '</div><!-- .lpm-combined -->';

		echo '</div><!-- .lpm-wrap -->';

		return ob_get_clean();
	}

	/**
	 * Enqueue assets — deferred until we know the shortcode was used.
	 * Chart.js is loaded from CDN only when needed.
	 */
	public function maybe_enqueue_assets(): void {
		// Always register; only enqueue when shortcode is present.
		wp_register_style(
			'lpm-style',
			LPM_PLUGIN_URL . 'assets/css/style.css',
			[],
			LPM_VERSION
		);

		wp_register_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			[],
			'4.4.0',
			true
		);

		wp_register_script(
			'lpm-main',
			LPM_PLUGIN_URL . 'assets/js/main.js',
			[ 'chartjs' ],
			LPM_VERSION,
			true
		);

		// Use late enqueue via wp_footer for pages where shortcode ran.
		add_action( 'wp_footer', [ $this, 'enqueue_if_used' ] );
	}

	public function enqueue_if_used(): void {
		if ( ! $this->shortcode_used ) {
			return;
		}
		wp_enqueue_style( 'lpm-style' );

		// Inject custom section-title color as a CSS variable if set.
		$title_color = sanitize_hex_color( get_option( 'lpm_section_title_color', '' ) );
		if ( $title_color ) {
			wp_add_inline_style( 'lpm-style', '.lpm-wrap { --lpm-section-title-color: ' . $title_color . '; }' );
		}

		wp_enqueue_script( 'lpm-main' );
	}
}

// Activation / deactivation.
register_activation_hook( __FILE__, function (): void {
	add_option( 'lpm_api_key',          '' );
	add_option( 'lpm_default_protocol', 'meteora' );
	add_option( 'lpm_cache_ttl',        300 );
} );

register_deactivation_hook( __FILE__, function (): void {
	// Transients are prefixed lpm_overview_* and lpm_revenue_* but WP has no wildcard delete.
	// They will expire naturally; no mass delete needed.
} );

add_action( 'plugins_loaded', function (): void {
	LP_Metrics::instance();
} );
