<?php
/**
 * Template: 1-month revenue date table.
 *
 * Variables available:
 *   $revenue  array  Parsed `data` array from the /revenue endpoint (already sorted ascending).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $revenue ) || ! is_array( $revenue ) ) {
	return;
}
?>

<div class="lpm-table-wrap">
	<table class="lpm-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', LPM_TEXT_DOMAIN ); ?></th>
				<th><?php esc_html_e( 'Daily PnL', LPM_TEXT_DOMAIN ); ?></th>
				<th><?php esc_html_e( 'Cumulative PnL', LPM_TEXT_DOMAIN ); ?></th>
				<th><?php esc_html_e( 'Invested', LPM_TEXT_DOMAIN ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( array_reverse( $revenue ) as $row ) :
				$date      = isset( $row['close_day'] ) ? date( 'M j, Y', strtotime( $row['close_day'] ) ) : '—';
				$daily     = is_numeric( $row['sum'] ?? null )            ? (float) $row['sum']            : null;
				$cumulative = is_numeric( $row['cumulative_pnl'] ?? null ) ? (float) $row['cumulative_pnl'] : null;
				$invested  = is_numeric( $row['total_invested'] ?? null ) ? (float) $row['total_invested'] : null;

				$daily_class = '';
				if ( null !== $daily ) {
					$daily_class = $daily >= 0 ? 'lpm-positive' : 'lpm-negative';
				}
				$cum_class = '';
				if ( null !== $cumulative ) {
					$cum_class = $cumulative >= 0 ? 'lpm-positive' : 'lpm-negative';
				}
			?>
			<tr>
				<td><?php echo esc_html( $date ); ?></td>
				<td class="<?php echo esc_attr( $daily_class ); ?>">
					<?php
					if ( null !== $daily ) {
						echo esc_html( ( $daily >= 0 ? '+$' : '-$' ) . number_format( abs( $daily ), 2 ) );
					} else {
						echo '—';
					}
					?>
				</td>
				<td class="<?php echo esc_attr( $cum_class ); ?>">
					<?php
					if ( null !== $cumulative ) {
						echo esc_html( ( $cumulative >= 0 ? '+$' : '-$' ) . number_format( abs( $cumulative ), 2 ) );
					} else {
						echo '—';
					}
					?>
				</td>
				<td>
					<?php
					if ( null !== $invested ) {
						$abs = abs( $invested );
						if ( $abs >= 1000000 ) {
							echo esc_html( '$' . number_format( $abs / 1000000, 2 ) . 'M' );
						} elseif ( $abs >= 1000 ) {
							echo esc_html( '$' . number_format( $abs / 1000, 2 ) . 'K' );
						} else {
							echo esc_html( '$' . number_format( $abs, 2 ) );
						}
					} else {
						echo '—';
					}
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div><!-- .lpm-table-wrap -->
