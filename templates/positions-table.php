<?php
/**
 * Template: Open LP positions table.
 *
 * Variables available (set by LP_Metrics::render_shortcode):
 *   $positions  array   Parsed `data` array from /opening endpoint.
 *   $wallet     string  Wallet address.
 *   $protocol   string  Protocol name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// USD formatter (same pattern as metrics-card.php).
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

// Human-readable age from ISO datetime.
$fmt_age = function( $datetime ) {
	if ( empty( $datetime ) ) return '—';
	$ts      = strtotime( $datetime );
	if ( ! $ts ) return '—';
	$seconds = time() - $ts;
	if ( $seconds < 3600 )  return max( 1, (int) ( $seconds / 60 ) ) . 'm';
	if ( $seconds < 86400 ) return (int) ( $seconds / 3600 ) . 'h';
	return (int) ( $seconds / 86400 ) . 'd';
};

$count = is_array( $positions ) ? count( $positions ) : 0;
?>

<div class="lpm-positions">

	<h3 class="lpm-section-title">
		<?php esc_html_e( 'Open Positions', LPM_TEXT_DOMAIN ); ?>
	</h3>

	<?php if ( $count === 0 ) : ?>
		<p class="lpm-empty"><?php esc_html_e( 'No open positions found for this wallet.', LPM_TEXT_DOMAIN ); ?></p>
	<?php else : ?>

	<div class="lpm-table-wrap">
		<table class="lpm-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pair', LPM_TEXT_DOMAIN ); ?></th>
					<th><?php esc_html_e( 'Current Value', LPM_TEXT_DOMAIN ); ?></th>
					<th><?php esc_html_e( 'PnL', LPM_TEXT_DOMAIN ); ?></th>
					<th><?php esc_html_e( 'Fees', LPM_TEXT_DOMAIN ); ?></th>
					<th><?php esc_html_e( 'DPR', LPM_TEXT_DOMAIN ); ?></th>
					<th><?php esc_html_e( 'Status', LPM_TEXT_DOMAIN ); ?></th>
					<th><?php esc_html_e( 'Age', LPM_TEXT_DOMAIN ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $positions as $pos ) :
					$pair_name     = $pos['pairName']        ?? '—';
					$current_val   = $pos['currentValue']    ?? null;
					$pnl_val       = $pos['pnl']['value']    ?? null;
					$pnl_pct       = $pos['pnl']['percent']  ?? null;
					$uncollected   = $pos['unCollectedFee']  ?? null;
					$dpr           = $pos['dpr']             ?? null;
					$in_range      = $pos['inRange']         ?? null;
					$created_at    = $pos['createdAt']       ?? null;
					$token0_addr     = $pos['token0'] ?? '';
					$token1_addr     = $pos['token1'] ?? '';
					$logo0           = $token0_addr ? 'https://orbmarkets.io/token/' . $token0_addr . '/logo?from=token' : '';
					$logo1           = $token1_addr ? 'https://orbmarkets.io/token/' . $token1_addr . '/logo?from=token' : '';
					$logo0_fallback  = $pos['logo0'] ?? '';
					$logo1_fallback  = $pos['logo1'] ?? '';

					$pnl_class = '';
					if ( is_numeric( $pnl_val ) ) {
						$pnl_class = (float) $pnl_val >= 0 ? 'lpm-positive' : 'lpm-negative';
					}
				?>
				<tr>
					<!-- Pair -->
					<td>
						<div class="lpm-token-pair">
							<?php if ( $logo0 || $logo0_fallback ) : ?>
								<img src="<?php echo esc_url( $logo0 ?: $logo0_fallback ); ?>" alt="" class="lpm-token-logo" width="16" height="16" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url( $logo0_fallback ); ?>'" />
							<?php endif; ?>
							<span><?php echo esc_html( $pair_name ); ?></span>
						</div>
					</td>

					<!-- Current Value -->
					<td><?php echo esc_html( $fmt_usd( $current_val ) ); ?></td>

					<!-- PnL -->
					<td class="<?php echo esc_attr( $pnl_class ); ?>">
						<?php echo esc_html( $fmt_usd( $pnl_val ) ); ?>
						<?php if ( is_numeric( $pnl_pct ) ) : ?>
							<span class="lpm-sub-pct"><?php echo esc_html( ( (float) $pnl_pct >= 0 ? '+' : '' ) . number_format( (float) $pnl_pct, 2 ) . '%' ); ?></span>
						<?php endif; ?>
					</td>

					<!-- Fees (uncollected only) -->
					<td class="lpm-positive">
						<?php echo esc_html( $fmt_usd( $uncollected ) ); ?>
					</td>

					<!-- DPR -->
					<td class="<?php echo is_numeric( $dpr ) && (float) $dpr >= 0 ? 'lpm-positive' : 'lpm-negative'; ?>">
						<?php
						if ( is_numeric( $dpr ) ) {
							echo esc_html( number_format( (float) $dpr * 100, 3 ) . '%' );
						} else {
							echo '—';
						}
						?>
					</td>

					<!-- In Range -->
					<td>
						<?php if ( null !== $in_range ) : ?>
							<span class="lpm-range-badge <?php echo $in_range ? 'lpm-range-badge--in' : 'lpm-range-badge--out'; ?>">
								<?php echo $in_range ? esc_html__( '✓ In Range', LPM_TEXT_DOMAIN ) : esc_html__( '✗ Out', LPM_TEXT_DOMAIN ); ?>
							</span>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>

					<!-- Age -->
					<td><?php echo esc_html( $fmt_age( $created_at ) ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div><!-- .lpm-table-wrap -->

	<?php endif; ?>

</div><!-- .lpm-positions -->
