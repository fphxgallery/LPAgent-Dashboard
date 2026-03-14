<?php
/**
 * Template: 1-month revenue chart (canvas only).
 *
 * Variables available:
 *   $revenue  array  Parsed `data` array from the /revenue endpoint.
 *   $wallet   string  Wallet address.
 *   $protocol string  Protocol name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $revenue ) || ! is_array( $revenue ) ) {
	echo '<div class="lpm-error">' . esc_html__( 'No revenue data available for this period.', LPM_TEXT_DOMAIN ) . '</div>';
	return;
}

// Sort ascending by date.
usort( $revenue, fn( $a, $b ) => strcmp( $a['close_day'] ?? '', $b['close_day'] ?? '' ) );

// Build arrays for Chart.js.
$labels       = [];
$cum_pnl      = [];
$daily_pnl    = [];

foreach ( $revenue as $row ) {
	$date = isset( $row['close_day'] )
		? date( 'M j', strtotime( $row['close_day'] ) )
		: '?';
	$labels[]    = $date;
	$cum_pnl[]   = is_numeric( $row['cumulative_pnl'] ?? null )  ? round( (float) $row['cumulative_pnl'], 2 )  : 0;
	$daily_pnl[] = is_numeric( $row['sum'] ?? null )             ? round( (float) $row['sum'], 2 )             : 0;
}

$chart_data = wp_json_encode( [
	'labels'    => $labels,
	'cumPnl'    => $cum_pnl,
	'dailyPnl'  => $daily_pnl,
] );

$canvas_id = 'lpm-chart-' . substr( md5( $wallet . $protocol ), 0, 8 );
?>

<div class="lpm-revenue">

	<h3 class="lpm-section-title">
		<?php esc_html_e( '1-Month Revenue', LPM_TEXT_DOMAIN ); ?>
	</h3>

	<div class="lpm-chart-wrap">
		<canvas
			id="<?php echo esc_attr( $canvas_id ); ?>"
			class="lpm-revenue-chart"
			data-points="<?php echo esc_attr( $chart_data ); ?>"
			aria-label="<?php esc_attr_e( '1-month cumulative PnL chart', LPM_TEXT_DOMAIN ); ?>"
			role="img"
		></canvas>
	</div>

</div><!-- .lpm-revenue -->
