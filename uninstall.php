<?php
/**
 * アンインストール時の後始末。
 *
 * プラグインが作成した transient と stale フォールバック用の option を削除する。
 *
 * @package WS_WP_Profile_Display_Block
 */

// WP-CLI やプラグイン画面以外からの直接実行を禁止する.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// transient は接頭辞 `ws_wporg_profile_v1_` で始まる（キー生成規則は Profile_Repository を参照）.
$ws_wp_profile_display_block_transient_like = $wpdb->esc_like( '_transient_ws_wporg_profile_v1_' ) . '%';
$ws_wp_profile_display_block_timeout_like   = $wpdb->esc_like( '_transient_timeout_ws_wporg_profile_v1_' ) . '%';
$ws_wp_profile_display_block_stale_like     = $wpdb->esc_like( 'ws_wporg_profile_stale_v1_' ) . '%';

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
		$ws_wp_profile_display_block_transient_like,
		$ws_wp_profile_display_block_timeout_like,
		$ws_wp_profile_display_block_stale_like
	)
);
// phpcs:enable
