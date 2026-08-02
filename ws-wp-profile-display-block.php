<?php
/**
 * Plugin Name:       WS WP Profile Display Block
 * Plugin URI:        https://github.com/web-soudan/ws-wp-profile-display-block
 * Description:       WordPress.org のプロフィール（アバター・表示名・登録日・バッジ）をブロックとして表示します。
 * Version:           0.1.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            株式会社Webの相談所
 * Author URI:        https://web-soudan.co.jp
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ws-wp-profile-display-block
 * Domain Path:       /languages
 *
 * @package WS_WP_Profile_Display_Block
 */

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WS_WP_PROFILE_DISPLAY_BLOCK_VERSION', '0.1.1' );
define( 'WS_WP_PROFILE_DISPLAY_BLOCK_FILE', __FILE__ );
define( 'WS_WP_PROFILE_DISPLAY_BLOCK_DIR', plugin_dir_path( __FILE__ ) );
define( 'WS_WP_PROFILE_DISPLAY_BLOCK_URL', plugin_dir_url( __FILE__ ) );

require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-validation.php';
require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-date-formatter.php';
require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-badge-icons.php';
require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-profile-parser.php';
require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-profile-not-found-exception.php';
require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-profile-fetcher.php';
require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-profile-repository.php';
require_once WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'includes/class-plugin.php';

WS_WP_Profile_Display_Block\Plugin::instance();
