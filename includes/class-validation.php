<?php
/**
 * 入力値の検証・正規化。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ユーザー名など、外部から渡される値の検証を担う純粋なクラス（外部依存なし）。
 */
class Validation {

	/**
	 * WordPress.org のユーザー名として受け付けるパターン。
	 *
	 * wp-profiles-card（Cloudflare Workers 版）の USERNAME_PATTERN と同一。
	 *
	 * @var string
	 */
	const USERNAME_PATTERN = '/^[a-z0-9][a-z0-9._-]{0,60}$/';

	/**
	 * ユーザー名を正規化する。
	 *
	 * @param mixed $raw 生の入力値。
	 * @return string|null 正規化したユーザー名。不正な値なら null。
	 */
	public static function normalize_username( $raw ) {
		if ( ! is_string( $raw ) ) {
			return null;
		}

		$username = strtolower( trim( $raw ) );

		return 1 === preg_match( self::USERNAME_PATTERN, $username ) ? $username : null;
	}
}
