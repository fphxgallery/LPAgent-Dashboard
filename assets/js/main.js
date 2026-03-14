/* global Chart */
( function () {
	'use strict';

	/**
	 * Initialise all LP Metrics revenue charts on the page.
	 * Each <canvas class="lpm-revenue-chart"> carries its data in a JSON
	 * data-points attribute set by the PHP template.
	 */
	function initCharts() {
		var canvases = document.querySelectorAll( '.lpm-revenue-chart' );

		if ( ! canvases.length || typeof Chart === 'undefined' ) {
			return;
		}

		canvases.forEach( function ( canvas ) {
			var raw = canvas.getAttribute( 'data-points' );
			if ( ! raw ) return;

			var points;
			try {
				points = JSON.parse( raw );
			} catch ( e ) {
				console.error( 'LP Metrics: failed to parse chart data', e );
				return;
			}

			var ctx = canvas.getContext( '2d' );

			// Purple-to-pink gradient fill under the line.
			var gradient = ctx.createLinearGradient( 0, 0, 0, canvas.parentElement.clientHeight || 280 );
			gradient.addColorStop( 0,   'rgba(124, 58, 237, 0.35)' );
			gradient.addColorStop( 0.6, 'rgba(236, 72, 153, 0.10)' );
			gradient.addColorStop( 1,   'rgba(236, 72, 153, 0)'    );

			// Bar colour per daily value (green / red).
			var dailyColours = ( points.dailyPnl || [] ).map( function ( v ) {
				return v >= 0 ? 'rgba(52, 211, 153, 0.7)' : 'rgba(248, 113, 113, 0.7)';
			} );

			new Chart( ctx, {
				type: 'bar',
				data: {
					labels: points.labels || [],
					datasets: [
						// Cumulative PnL — drawn as a line on top.
						{
							type:              'line',
							label:             'Cumulative PnL ($)',
							data:              points.cumPnl || [],
							borderColor:       'rgba(124, 58, 237, 1)',
							borderWidth:       2.5,
							pointRadius:       0,
							pointHoverRadius:  4,
							fill:              true,
							backgroundColor:   gradient,
							tension:           0.35,
							yAxisID:           'yCum',
							order:             1,
						},
						// Daily PnL bars.
						{
							type:              'bar',
							label:             'Daily PnL ($)',
							data:              points.dailyPnl || [],
							backgroundColor:   dailyColours,
							borderRadius:      3,
							yAxisID:           'yDaily',
							order:             2,
						},
					],
				},
				options: {
					responsive:          true,
					maintainAspectRatio: false,
					interaction: {
						mode:      'index',
						intersect: false,
					},
					plugins: {
						legend: {
							display: true,
							labels: {
								color:    '#94a3b8',
								font:     { family: "'Space Grotesk', 'Inter', sans-serif", size: 12 },
								boxWidth: 14,
							},
						},
						tooltip: {
							backgroundColor: 'rgba(15, 15, 30, 0.9)',
							titleColor:      '#f1f5f9',
							bodyColor:       '#94a3b8',
							borderColor:     'rgba(255,255,255,0.1)',
							borderWidth:     1,
							padding:         12,
							titleFont:       { family: "'Space Grotesk', 'Inter', sans-serif", weight: 700 },
							callbacks: {
								label: function ( ctx ) {
									var val  = ctx.parsed.y;
									var sign = val >= 0 ? '+$' : '-$';
									return ' ' + ctx.dataset.label + ': ' + sign + Math.abs( val ).toLocaleString( undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
								},
							},
						},
					},
					scales: {
						x: {
							ticks: {
								color:     '#94a3b8',
								maxRotation: 0,
								autoSkip:  true,
								maxTicksLimit: 10,
								font:      { family: "'Space Grotesk', 'Inter', sans-serif", size: 11 },
							},
							grid: {
								color: 'rgba(255,255,255,0.04)',
							},
						},
						yCum: {
							position: 'left',
							ticks: {
								color: '#94a3b8',
								font:  { family: "'Space Grotesk', 'Inter', sans-serif", size: 11 },
								callback: function ( v ) {
									if ( Math.abs( v ) >= 1000 ) return '$' + ( v / 1000 ).toFixed( 1 ) + 'K';
									return '$' + v.toFixed( 0 );
								},
							},
							grid: {
								color: 'rgba(255,255,255,0.06)',
							},
						},
						yDaily: {
							position: 'right',
							display:  false,
							grid:     { drawOnChartArea: false },
						},
					},
				},
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initCharts );
	} else {
		initCharts();
	}
} )();
