<?php
/**
 * LPAgent API client.
 *
 * Wraps the two open-API endpoints used by the plugin:
 *   - /open-api/v1/lp-positions/overview
 *   - /open-api/v1/lp-positions/revenue/{owner}
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LPM_API_Client {

	private const BASE_URL = 'https://api.lpagent.io/open-api/v1/lp-positions/';
	private const TIMEOUT  = 10;

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Fetch overview metrics for a wallet.
	 *
	 * @return array|WP_Error  Parsed `data` sub-array on success, WP_Error on failure.
	 */
	public function get_overview( string $owner, string $protocol = 'meteora' ) {
		$cache_key = 'lpm_overview_' . md5( $owner . $protocol );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$url = add_query_arg(
			[
				'owner'    => $owner,
				'protocol' => $protocol,
			],
			self::BASE_URL . 'overview'
		);

		$response = $this->request( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data'] ) ) {
			return new WP_Error( 'lpm_no_data', __( 'No overview data returned for this wallet.', LPM_TEXT_DOMAIN ) );
		}

		// The API returns data as an array of objects — take the first element.
		$data = isset( $response['data'][0] ) ? $response['data'][0] : $response['data'];

		$ttl = absint( get_option( 'lpm_cache_ttl', 300 ) );
		set_transient( $cache_key, $data, $ttl );

		return $data;
	}

	/**
	 * Fetch 1-month daily revenue data for a wallet.
	 *
	 * @return array|WP_Error  Parsed `data` array on success, WP_Error on failure.
	 */
	public function get_revenue( string $owner, string $protocol = 'meteora' ) {
		$cache_key = 'lpm_revenue_' . md5( $owner . $protocol );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$url = add_query_arg(
			[
				'range'    => '1M',
				'period'   => 'day',
				'protocol' => $protocol,
			],
			self::BASE_URL . 'revenue/' . rawurlencode( $owner )
		);

		$response = $this->request( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return new WP_Error( 'lpm_no_data', __( 'No revenue data returned for this wallet.', LPM_TEXT_DOMAIN ) );
		}

		$ttl = absint( get_option( 'lpm_cache_ttl', 300 ) );
		set_transient( $cache_key, $response['data'], $ttl );

		return $response['data'];
	}

	/**
	 * Fetch currently open LP positions for a wallet.
	 *
	 * @return array|WP_Error  Parsed `data` array on success, WP_Error on failure.
	 */
	public function get_positions( string $owner ) {
		$cache_key = 'lpm_positions_' . md5( $owner );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$url = add_query_arg(
			[ 'owner' => $owner ],
			self::BASE_URL . 'opening'
		);

		$response = $this->request( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return new WP_Error( 'lpm_no_data', __( 'No open positions data returned for this wallet.', LPM_TEXT_DOMAIN ) );
		}

		$ttl = absint( get_option( 'lpm_cache_ttl', 300 ) );
		set_transient( $cache_key, $response['data'], $ttl );

		return $response['data'];
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Perform a GET request with the stored API key and return the decoded body.
	 *
	 * @return array|WP_Error
	 */
	private function request( string $url ) {
		$api_key = trim( get_option( 'lpm_api_key', '' ) );

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'lpm_no_api_key',
				__( 'LP Metrics: API key is not configured. Please add it in Settings > LP Metrics.', LPM_TEXT_DOMAIN )
			);
		}

		$response = wp_remote_get( $url, [
			'timeout' => self::TIMEOUT,
			'headers' => [
				'x-api-key'  => $api_key,
				'Accept'     => 'application/json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; LP-Metrics/' . LPM_VERSION,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'lpm_request_failed',
				sprintf(
					/* translators: %s: original error message */
					__( 'LP Metrics API request failed: %s', LPM_TEXT_DOMAIN ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'lpm_invalid_json', __( 'LP Metrics: invalid JSON response from API.', LPM_TEXT_DOMAIN ) );
		}

		if ( 200 !== (int) $code ) {
			$message = $data['message'] ?? sprintf(
				/* translators: %d: HTTP status code */
				__( 'HTTP error %d from LPAgent API.', LPM_TEXT_DOMAIN ),
				$code
			);
			return new WP_Error( 'lpm_api_error', $message );
		}

		return $data;
	}
}
