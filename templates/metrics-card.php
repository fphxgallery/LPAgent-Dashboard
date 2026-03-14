<?php
/**
 * Template: Overview metrics cards.
 *
 * Variables available (set by LP_Metrics::render_shortcode):
 *   $overview  array  Parsed `data` from the /overview endpoint.
 *   $protocol  string  Protocol name (for display).
 *   $wallet    string  Wallet address (truncated for display).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Helpers.
$fmt_usd = function( $val ) {
	if ( ! is_numeric( $val ) ) return '—';
	$val = (float) $val;
	$abs = abs( $val );
	if ( $abs >= 1000000 ) {
		return ( $val < 0 ? '-' : '' ) . '$' . number_format( $abs / 1000000, 2 ) . 'M';
	}
	if ( $abs >= 1000 ) {
		return ( $val < 0 ? '-' : '' ) . '$' . number_format( $abs / 1000, 2 ) . 'K';
	}
	return ( $val < 0 ? '-$' : '$' ) . number_format( $abs, 2 );
};

$fmt_pct = function( $val, $decimals = 2 ) {
	if ( ! is_numeric( $val ) ) return '—';
	return number_format( (float) $val * 100, $decimals ) . '%';
};

$fmt_sol = function( $val ) {
	if ( ! is_numeric( $val ) ) return '—';
	return number_format( (float) $val, 4 ) . ' SOL';
};

// Helper: read a time-range breakdown field, preferring 1M with ALL as fallback.
$breakdown = function( $field, $range = '1M' ) use ( $overview ) {
	if ( is_array( $field ) ) {
		$val = $field[ $range ] ?? $field['ALL'] ?? null;
		return is_numeric( $val ) ? $val : null;
	}
	return is_numeric( $field ) ? $field : null;
};

// Extract values — prefer 1M, fall back to ALL.
$pnl_1m        = $breakdown( $overview['total_pnl']         ?? null );
$pnl_native_1m = $breakdown( $overview['total_pnl_native']  ?? null );
$fee_1m        = $breakdown( $overview['total_fee']         ?? null );
$win_rate_1m   = $breakdown( $overview['win_rate']          ?? null );
$opening       = $overview['opening_lp']  ?? null;
$total_lp      = $overview['total_lp']    ?? null;
$apr           = $overview['apr']         ?? null;
$roi           = $overview['roi']         ?? null;
$avg_age       = $overview['avg_age_hour'] ?? null;

// Determine if PnL is positive/negative for colour coding.
$pnl_class = '';
if ( is_numeric( $pnl_1m ) ) {
	$pnl_class = (float) $pnl_1m >= 0 ? 'lpm-positive' : 'lpm-negative';
}

$wallet_short = strlen( $wallet ) > 12
	? substr( $wallet, 0, 6 ) . '…' . substr( $wallet, -4 )
	: $wallet;

?>

<div class="lpm-overview">

	<div class="lpm-stats-grid">

		<!-- PnL -->
		<div class="lpm-stat-card lpm-glass <?php echo esc_attr( $pnl_class ); ?>">
			<span class="lpm-stat-card__label"><?php esc_html_e( 'Total PnL', LPM_TEXT_DOMAIN ); ?></span>
			<span class="lpm-stat-card__value"><?php echo esc_html( $fmt_usd( $pnl_1m ) ); ?></span>
			<span class="lpm-stat-card__sub"><?php echo esc_html( $fmt_sol( $pnl_native_1m ) ); ?></span>
		</div>

		<!-- Fees earned -->
		<div class="lpm-stat-card lpm-glass">
			<span class="lpm-stat-card__label"><?php esc_html_e( 'Fees Earned', LPM_TEXT_DOMAIN ); ?></span>
			<span class="lpm-stat-card__value lpm-positive"><?php echo esc_html( $fmt_usd( $fee_1m ) ); ?></span>
		</div>

		<!-- Win Rate -->
		<div class="lpm-stat-card lpm-glass">
			<span class="lpm-stat-card__label"><?php esc_html_e( 'Win Rate', LPM_TEXT_DOMAIN ); ?></span>
			<span class="lpm-stat-card__value"><?php echo esc_html( $fmt_pct( $win_rate_1m ) ); ?></span>
		</div>

	</div><!-- .lpm-stats-grid -->

</div><!-- .lpm-overview -->
