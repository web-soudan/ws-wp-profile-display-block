<?php
/**
 * WordPress.org のプロフィールページを取得する。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTTP 通信を担うクラス。取得結果は Profile_Parser には渡さず、生の HTML を返す
 * （パースと通信を分離し、Profile_Parser をテストしやすくするため）。
 */
class Profile_Fetcher {

	/**
	 * プロフィールページのベース URL。
	 *
	 * @var string
	 */
	const PROFILE_BASE_URL = 'https://profiles.wordpress.org/';

	/**
	 * リクエストのタイムアウト（秒）。
	 *
	 * @var int
	 */
	const TIMEOUT_SECONDS = 5;

	/**
	 * プロフィールページの HTML を取得する。
	 *
	 * @param string $username 検証済みのユーザー名。
	 * @return string 取得した HTML。
	 * @throws Profile_Not_Found_Exception 取得に失敗した場合。
	 */
	public function fetch( $username ) {
		$response = wp_remote_get(
			self::PROFILE_BASE_URL . rawurlencode( $username ) . '/',
			array(
				'timeout'    => self::TIMEOUT_SECONDS,
				'user-agent' => sprintf( 'ws-wp-profile-display-block/%s (+%s)', WS_WP_PROFILE_DISPLAY_BLOCK_VERSION, home_url( '/' ) ),
				'headers'    => array( 'Accept' => 'text/html' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			throw new Profile_Not_Found_Exception( $username ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Profile_Not_Found_Exception のコンストラクタ内で esc_html() 済み.
		}

		return wp_remote_retrieve_body( $response );
	}
}
