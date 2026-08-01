<?php
/**
 * プラグインの初期化を担う。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * フック登録の起点となるシングルトンクラス。
 */
class Plugin {

	/**
	 * 唯一のインスタンス。
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * インスタンスを取得する（無ければ生成する）。
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * フックを登録する。
	 */
	private function init() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/*
		 * WP_Block_Supports::init() は 'init' フックの優先度 22 で全ブロックの
		 * attributes.style を一律 array( 'type' => 'object' ) に上書きする
		 * （register_block() 内で default を設定しても優先度 10 だと後から消される）。
		 * そのため、それより後の優先度で初期値を設定する.
		 */
		add_action( 'init', array( $this, 'set_default_border_and_shadow' ), 30 );
	}

	/**
	 * 翻訳ファイルを読み込む。
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'ws-wp-profile-display-block',
			false,
			dirname( plugin_basename( WS_WP_PROFILE_DISPLAY_BLOCK_FILE ) ) . '/languages'
		);
	}

	/**
	 * ブロックを登録する。
	 */
	public function register_block() {
		register_block_type( WS_WP_PROFILE_DISPLAY_BLOCK_DIR . 'build/profile-card' );
	}

	/**
	 * カードの角丸・影の初期値を設定する。
	 *
	 * block.json の attributes.style.default に直接書いても、border・shadow の
	 * block supports 登録処理（wp-includes/block-supports/border.php,
	 * shadow.php が 'init' 優先度 22 の WP_Block_Supports::init() から呼ばれる）が
	 * attributes.style を `array( 'type' => 'object' )` へ**無条件で上書き**するため、
	 * default は反映される前に消えてしまう。そのため、その上書きが終わったあと
	 * （優先度 30）に、登録済みの WP_Block_Type オブジェクトへ直接 default を設定する。
	 */
	public function set_default_border_and_shadow() {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'ws-profile/wporg-card' );

		if ( ! $block_type instanceof \WP_Block_Type || ! isset( $block_type->attributes['style'] ) ) {
			return;
		}

		$block_type->attributes['style']['default'] = array(
			'border' => array(
				'radius' => '16px',
			),
			// rgba() は WordPress コアの safecss_filter_attr()（wp-includes/kses.php）に
			// 「安全でない CSS」として無言で破棄されるため、8 桁 HEX（アルファ付き）で指定する
			// （docs/spec.md 5.3 参照）.
			'shadow' => '0px 1px 3px #0000000f, 0px 8px 24px #00000014',
		);
	}
}
