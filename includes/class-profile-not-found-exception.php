<?php
/**
 * 該当ユーザーが見つからなかったことを表す例外。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile_Fetcher::fetch() が取得に失敗した際に投げる例外。
 */
class Profile_Not_Found_Exception extends \Exception {

	/**
	 * コンストラクタ。
	 *
	 * @param string $username 対象のユーザー名。
	 */
	public function __construct( $username ) {
		parent::__construct(
			sprintf(
				/* translators: %s: WordPress.org のユーザー名 */
				esc_html__( 'WordPress.org のプロフィールが見つかりません: %s', 'ws-wp-profile-display-block' ),
				esc_html( $username )
			)
		);
	}
}
