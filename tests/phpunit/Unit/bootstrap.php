<?php
/**
 * PHPUnit bootstrap file for Unit tests.
 *
 * WordPress をロードしない軽量な bootstrap。テスト対象クラス（Profile_Parser 等）は
 * ファイル先頭で `ABSPATH` 未定義時に直接アクセスとみなして exit する実装のため、
 * ダミーの ABSPATH を定義してからオートロードする。
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// 本来はプラグイン本体（ws-wp-profile-display-block.php）が定義する定数.
// Badge_Icons::custom_icon_path() が assets/badges/ を解決するために参照する.
if ( ! defined( 'WS_WP_PROFILE_DISPLAY_BLOCK_DIR' ) ) {
	define( 'WS_WP_PROFILE_DISPLAY_BLOCK_DIR', dirname( __DIR__, 3 ) . '/' );
}

require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';
