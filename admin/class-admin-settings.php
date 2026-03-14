<?php
/**
 * Admin settings page for LP Metrics.
 *
 * Adds a settings page under Settings > LP Metrics with:
 *   - API key (password field)
 *   - Default protocol (select)
 *   - Cache TTL (number)
 *   - Manual cache clear button
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LPM_Admin_Settings {

	private const OPTION_GROUP = 'lpm_settings_group';
	private const PAGE_SLUG    = 'lp-metrics-settings';

	public function __construct() {
		add_action( 'admin_menu',    [ $this, 'add_menu' ] );
		add_action( 'admin_init',    [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_color_picker' ] );
		add_action( 'admin_post_lpm_clear_cache', [ $this, 'handle_clear_cache' ] );
	}

	public function add_menu(): void {
		add_options_page(
			__( 'LP Metrics Settings', LPM_TEXT_DOMAIN ),
			__( 'LP Metrics', LPM_TEXT_DOMAIN ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting( self::OPTION_GROUP, 'lpm_api_key',           [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
		register_setting( self::OPTION_GROUP, 'lpm_default_protocol',  [ 'sanitize_callback' => 'sanitize_text_field', 'default' => 'meteora' ] );
		register_setting( self::OPTION_GROUP, 'lpm_cache_ttl',         [ 'sanitize_callback' => 'absint',              'default' => 300 ] );
		register_setting( self::OPTION_GROUP, 'lpm_section_title_color', [
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '',
		] );

		add_settings_section(
			'lpm_section_api',
			__( 'API Configuration', LPM_TEXT_DOMAIN ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'lpm_api_key',
			__( 'LPAgent API Key', LPM_TEXT_DOMAIN ),
			[ $this, 'render_api_key_field' ],
			self::PAGE_SLUG,
			'lpm_section_api'
		);

		add_settings_field(
			'lpm_default_protocol',
			__( 'Default Protocol', LPM_TEXT_DOMAIN ),
			[ $this, 'render_protocol_field' ],
			self::PAGE_SLUG,
			'lpm_section_api'
		);

		add_settings_field(
			'lpm_cache_ttl',
			__( 'Cache Duration (seconds)', LPM_TEXT_DOMAIN ),
			[ $this, 'render_ttl_field' ],
			self::PAGE_SLUG,
			'lpm_section_api'
		);

		add_settings_section(
			'lpm_section_display',
			__( 'Display', LPM_TEXT_DOMAIN ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'lpm_section_title_color',
			__( 'Section Title Color', LPM_TEXT_DOMAIN ),
			[ $this, 'render_title_color_field' ],
			self::PAGE_SLUG,
			'lpm_section_display'
		);
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	public function render_api_key_field(): void {
		$value = get_option( 'lpm_api_key', '' );
		printf(
			'<input type="password" id="lpm_api_key" name="lpm_api_key" value="%s" class="regular-text" autocomplete="new-password" />
			<p class="description">%s</p>',
			esc_attr( $value ),
			esc_html__( 'Your LPAgent open-API key. Get one at lpagent.io.', LPM_TEXT_DOMAIN )
		);
	}

	public function render_protocol_field(): void {
		$value     = get_option( 'lpm_default_protocol', 'meteora' );
		$protocols = [
			'meteora'          => 'Meteora',
			'meteora_damm_v2'  => 'Meteora DAMM v2',
			'orca'             => 'Orca',
		];
		echo '<select id="lpm_default_protocol" name="lpm_default_protocol">';
		foreach ( $protocols as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Default protocol used when the shortcode omits the protocol attribute.', LPM_TEXT_DOMAIN ) . '</p>';
	}

	public function render_ttl_field(): void {
		$value = absint( get_option( 'lpm_cache_ttl', 300 ) );
		printf(
			'<input type="number" id="lpm_cache_ttl" name="lpm_cache_ttl" value="%d" min="60" max="86400" class="small-text" /> %s
			<p class="description">%s</p>',
			$value,
			esc_html__( 'seconds', LPM_TEXT_DOMAIN ),
			esc_html__( 'How long to cache API responses. Minimum 60 s, maximum 86 400 s (24 h).', LPM_TEXT_DOMAIN )
		);
	}

	public function render_title_color_field(): void {
		$value = get_option( 'lpm_section_title_color', '' );
		printf(
			'<input type="text" id="lpm_section_title_color" name="lpm_section_title_color" value="%s" class="lpm-color-picker" data-default-color="" />
			<p class="description">%s</p>',
			esc_attr( $value ),
			esc_html__( 'Color for the "1-Month Revenue" and "Open Positions" headings. Leave blank to use the theme default.', LPM_TEXT_DOMAIN )
		);
	}

	public function enqueue_color_picker( string $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){ $(".lpm-color-picker").wpColorPicker(); });'
		);
	}

	// -------------------------------------------------------------------------
	// Page renderer
	// -------------------------------------------------------------------------

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LP Metrics Settings', LPM_TEXT_DOMAIN ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Cache Management', LPM_TEXT_DOMAIN ); ?></h2>
			<p><?php esc_html_e( 'Clears all cached LP Metrics API responses.', LPM_TEXT_DOMAIN ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lpm_clear_cache" />
				<?php wp_nonce_field( 'lpm_clear_cache_nonce' ); ?>
				<?php submit_button( __( 'Clear Cache', LPM_TEXT_DOMAIN ), 'secondary' ); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Shortcode Usage', LPM_TEXT_DOMAIN ); ?></h2>
			<p>
				<?php esc_html_e( 'Add the following shortcode to any page or post:', LPM_TEXT_DOMAIN ); ?>
			</p>
			<code>[lp_metrics wallet="YOUR_SOLANA_WALLET_ADDRESS"]</code>
			<p>
				<?php esc_html_e( 'Optional: override the protocol per-shortcode:', LPM_TEXT_DOMAIN ); ?>
			</p>
			<code>[lp_metrics wallet="YOUR_SOLANA_WALLET_ADDRESS" protocol="orca"]</code>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Cache clear handler
	// -------------------------------------------------------------------------

	public function handle_clear_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', LPM_TEXT_DOMAIN ) );
		}

		check_admin_referer( 'lpm_clear_cache_nonce' );

		// WP doesn't support wildcard transient deletion — use wpdb directly.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_lpm_overview_' ) . '%',
				$wpdb->esc_like( '_transient_lpm_revenue_' ) . '%'
			)
		);
		// Also remove timeout rows.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_lpm_overview_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_lpm_revenue_' ) . '%'
			)
		);

		wp_safe_redirect( add_query_arg( [ 'page' => 'lp-metrics-settings', 'cache_cleared' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}
}
