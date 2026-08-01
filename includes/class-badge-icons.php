<?php
/**
 * バッジ slug からアイコンと配色を解決する。
 *
 * WordPress コア同梱の Dashicons で描画できないバッジは、独自 SVG（assets/badges/）に
 * フォールバックする。配色は wp-profiles-card の src/lib/draw-card.js（63 種）を移植した。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * バッジのアイコン・配色の解決を担う純粋なクラス（外部依存なし）。
 */
class Badge_Icons {

	/**
	 * WordPress コアの Dashicons に対応が無く、独自 SVG が必要なアイコン名の一覧。
	 *
	 * ファイル名は `dashicons-{badge-slug}` の形（Profile_Parser がフォールバックとして
	 * 生成するアイコン名と一致させている）。実体は assets/badges/{値}.svg。
	 *
	 * @var string[]
	 */
	const CUSTOM_ICON_NAMES = array(
		'dashicons-badge-buddypress-contributor',
		'dashicons-badge-campus-connect-participant',
		'dashicons-badge-core-ai-contributor',
		'dashicons-badge-core-ai-team',
		'dashicons-badge-credits-graduate',
		'dashicons-badge-credits-mentor',
		'dashicons-badge-openverse-contributor',
		'dashicons-badge-performance-contributor',
		'dashicons-badge-performance-team',
		'dashicons-badge-playground-contributor',
	);

	/**
	 * 未知のバッジに使うフォールバック配色。
	 *
	 * @var string
	 */
	const DEFAULT_COLOR = '#C7C7C7';

	/**
	 * バッジ slug ごとの基準色。
	 *
	 * 枠線とアイコンの色として使う。背景は `filled` が true のとき、この色の 25% 透過。
	 * 移植元: wp-profiles-card/src/lib/draw-card.js（付録 A 参照。dashicons-badge-credits-mentor
	 * の元 CSS は rgb(131, 41, 277) と青が範囲外だったため 255 にクランプ済み）。
	 *
	 * @var array<string, string>
	 */
	const COLORS = array(
		'badge-accessibility'              => '#11799D',
		'badge-accessibility-contributor'  => '#11799D',
		'badge-bbpress'                    => '#2D8E42',
		'badge-bbpress-contributor'        => '#2D8E42',
		'badge-buddypress'                 => '#D84800',
		'badge-buddypress-contributor'     => '#D84800',
		'badge-campus-connect-participant' => '#3858E9',
		'badge-code'                       => '#CD0000',
		'badge-code-committer'             => '#CD0000',
		'badge-community'                  => '#11799D',
		'badge-community-contributor'      => '#11799D',
		'badge-core-ai-contributor'        => '#7A00DF',
		'badge-core-ai-team'               => '#7A00DF',
		'badge-credits-graduate'           => '#AE47F2',
		'badge-credits-mentor'             => '#8329FF',
		'badge-design'                     => '#EEC26A',
		'badge-design-contributor'         => '#EEC26A',
		'badge-documentation'              => '#3B7236',
		'badge-documentation-contributor'  => '#3B7236',
		'badge-hosting'                    => '#5358A6',
		'badge-hosting-contributor'        => '#5358A6',
		'badge-marketing'                  => '#47BEA7',
		'badge-marketing-contributor'      => '#47BEA7',
		'badge-media-corps-contributor'    => '#139F94',
		'badge-media-corps-team'           => '#139F94',
		'badge-meta'                       => '#AEADAD',
		'badge-meta-contributor'           => '#AEADAD',
		'badge-mobile'                     => '#FBA16C',
		'badge-openverse'                  => '#C52B9B',
		'badge-openverse-contributor'      => '#C52B9B',
		'badge-organizer'                  => '#F7AD43',
		'badge-pattern-author'             => '#924BB3',
		'badge-patterns-team'              => '#924BB3',
		'badge-performance-contributor'    => '#0073AA',
		'badge-performance-team'           => '#0073AA',
		'badge-photo-contributor'          => '#3B7236',
		'badge-photos-team'                => '#3B7236',
		'badge-playground-contributor'     => '#3858E9',
		'badge-plugins'                    => '#F06723',
		'badge-plugins-reviewer'           => '#F06723',
		'badge-security-contributor'       => '#00CC3A',
		'badge-security-team'              => '#00CC3A',
		'badge-speaker'                    => '#F7AD43',
		'badge-support'                    => '#33B4CE',
		'badge-support-contributor'        => '#33B4CE',
		'badge-sustainability-contributor' => '#177F6A',
		'badge-sustainability-team'        => '#177F6A',
		'badge-test'                       => '#008080',
		'badge-test-contributor'           => '#008080',
		'badge-themes'                     => '#4E3288',
		'badge-themes-reviewer'            => '#4E3288',
		'badge-tide'                       => '#1526FF',
		'badge-tide-contributor'           => '#1526FF',
		'badge-training'                   => '#E9C02D',
		'badge-training-contributor'       => '#E9C02D',
		'badge-translation-contributor'    => '#C32283',
		'badge-translation-editor'         => '#C32283',
		'badge-unknown'                    => self::DEFAULT_COLOR,
		'badge-wordcamp-volunteer'         => '#F7AD43',
		'badge-wordpress-tv'               => '#73AD30',
		'badge-wordpress-tv-contributor'   => '#73AD30',
		'badge-wp-cli'                     => '#424242',
		'badge-wp-cli-contributor'         => '#424242',
	);

	/**
	 * 背景を「塗り」にする slug の一覧（それ以外はアウトライン ＝ 背景白）。
	 *
	 * @var string[]
	 */
	const FILLED_SLUGS = array(
		'badge-accessibility',
		'badge-bbpress',
		'badge-code-committer',
		'badge-community',
		'badge-core-ai-team',
		'badge-design',
		'badge-documentation',
		'badge-hosting',
		'badge-marketing',
		'badge-meta',
		'badge-mobile',
		'badge-openverse',
		'badge-patterns-team',
		'badge-performance-team',
		'badge-photos-team',
		'badge-plugins-reviewer',
		'badge-security-team',
		'badge-support',
		'badge-test',
		'badge-themes-reviewer',
		'badge-tide',
		'badge-training',
		'badge-translation-editor',
		'badge-wordpress-tv',
		'badge-wp-cli',
	);

	/**
	 * バッジ slug から基準色（HEX）を返す。
	 *
	 * @param string $slug バッジ slug（例 "badge-code"）。
	 * @return string HEX カラーコード（# 付き）。未知の slug は既定色。
	 */
	public static function get_color( $slug ) {
		return self::COLORS[ $slug ] ?? self::DEFAULT_COLOR;
	}

	/**
	 * バッジ slug が「塗り」表示かどうかを返す。
	 *
	 * @param string $slug バッジ slug。
	 * @return bool 塗りなら true、アウトラインなら false。
	 */
	public static function is_filled( $slug ) {
		return in_array( $slug, self::FILLED_SLUGS, true );
	}

	/**
	 * アイコン名が独自 SVG を必要とするかどうかを返す。
	 *
	 * @param string $icon_name Profile_Parser が抽出したアイコン名（例 "dashicons-editor-code"）。
	 * @return bool 独自 SVG が必要なら true。
	 */
	public static function is_custom_icon( $icon_name ) {
		return in_array( $icon_name, self::CUSTOM_ICON_NAMES, true );
	}

	/**
	 * 独自 SVG アイコンのファイルシステムパスを返す。
	 *
	 * @param string $icon_name アイコン名（例 "dashicons-badge-core-ai-team"）。
	 * @return string|null 存在すればファイルパス、無ければ null。
	 */
	public static function custom_icon_path( $icon_name ) {
		if ( ! self::is_custom_icon( $icon_name ) ) {
			return null;
		}

		$path = WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'assets/badges/' . $icon_name . '.svg';

		return file_exists( $path ) ? $path : null;
	}

	/**
	 * WordPress コアの Dashicon クラス名として使えるかどうかを返す。
	 *
	 * 独自 SVG が必要なもの・空文字は対象外。
	 *
	 * @param string $icon_name アイコン名。
	 * @return bool
	 */
	public static function is_core_dashicon( $icon_name ) {
		return '' !== $icon_name && ! self::is_custom_icon( $icon_name );
	}
}
