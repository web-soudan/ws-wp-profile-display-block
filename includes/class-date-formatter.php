<?php
/**
 * WordPress.org の登録日表記（英語・序数付き）をパースする。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "June 8th, 2014" 形式の日付文字列を扱う純粋なクラス（外部依存なし）。
 */
class Date_Formatter {

	/**
	 * WordPress.org の登録日表記を DateTimeImmutable に変換する。
	 *
	 * @param string $raw 登録日の原文（例 "June 8th, 2014"）。
	 * @return \DateTimeImmutable|null パースできれば日時、できなければ null。
	 */
	public static function parse( $raw ) {
		$raw = trim( (string) $raw );

		if ( '' === $raw ) {
			return null;
		}

		// 序数サフィックス（st/nd/rd/th）を除去してからパースする.
		$normalized = preg_replace( '/(\d+)(st|nd|rd|th)/i', '$1', $raw );

		$date = \DateTimeImmutable::createFromFormat( 'F j, Y', $normalized );

		return false !== $date ? $date : null;
	}
}
