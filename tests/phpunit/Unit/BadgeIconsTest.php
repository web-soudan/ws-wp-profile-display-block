<?php
/**
 * Badge_Icons のテスト。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WS_WP_Profile_Display_Block\Badge_Icons;

/**
 * @covers \WS_WP_Profile_Display_Block\Badge_Icons
 */
class BadgeIconsTest extends TestCase {

	/**
	 * get_color() のテスト。
	 */
	public function test_get_color() {
		$test_cases = array(
			array(
				'test_condition_name' => '既知の slug（badge-code）の場合 => 定義済みの色',
				'slug'                 => 'badge-code',
				'expected'             => '#CD0000',
			),
			array(
				'test_condition_name' => '既知の slug（badge-plugins-reviewer）の場合 => 定義済みの色',
				'slug'                 => 'badge-plugins-reviewer',
				'expected'             => '#F06723',
			),
			array(
				'test_condition_name' => '未知の slug の場合 => 既定のグレー',
				'slug'                 => 'badge-something-new',
				'expected'             => Badge_Icons::DEFAULT_COLOR,
			),
		);

		foreach ( $test_cases as $case ) {
			$this->assertSame( $case['expected'], Badge_Icons::get_color( $case['slug'] ), $case['test_condition_name'] );
		}
	}

	/**
	 * is_filled() のテスト。
	 */
	public function test_is_filled() {
		$test_cases = array(
			array(
				'test_condition_name' => '塗り指定の slug（badge-code-committer）の場合 => true',
				'slug'                 => 'badge-code-committer',
				'expected'             => true,
			),
			array(
				'test_condition_name' => 'アウトライン指定の slug（badge-code）の場合 => false',
				'slug'                 => 'badge-code',
				'expected'             => false,
			),
			array(
				'test_condition_name' => '未知の slug の場合 => false（アウトライン扱い）',
				'slug'                 => 'badge-something-new',
				'expected'             => false,
			),
		);

		foreach ( $test_cases as $case ) {
			$this->assertSame( $case['expected'], Badge_Icons::is_filled( $case['slug'] ), $case['test_condition_name'] );
		}
	}

	/**
	 * is_custom_icon() のテスト。
	 */
	public function test_is_custom_icon() {
		$test_cases = array(
			array(
				'test_condition_name' => '独自 SVG が必要なアイコン名の場合 => true',
				'icon_name'            => 'dashicons-badge-core-ai-team',
				'expected'             => true,
			),
			array(
				'test_condition_name' => 'WordPress コアの Dashicon 名の場合 => false',
				'icon_name'            => 'dashicons-editor-code',
				'expected'             => false,
			),
			array(
				'test_condition_name' => '空文字の場合 => false',
				'icon_name'            => '',
				'expected'             => false,
			),
		);

		foreach ( $test_cases as $case ) {
			$this->assertSame( $case['expected'], Badge_Icons::is_custom_icon( $case['icon_name'] ), $case['test_condition_name'] );
		}
	}

	/**
	 * custom_icon_path() のテスト。
	 */
	public function test_custom_icon_path() {
		$test_cases = array(
			array(
				'test_condition_name' => '独自 SVG が同梱されているアイコン名の場合 => ファイルパスが返る',
				'icon_name'            => 'dashicons-badge-core-ai-team',
				'expect_null'          => false,
			),
			array(
				'test_condition_name' => 'コア Dashicon 名の場合 => null（独自 SVG ではない）',
				'icon_name'            => 'dashicons-editor-code',
				'expect_null'          => true,
			),
		);

		foreach ( $test_cases as $case ) {
			$actual = Badge_Icons::custom_icon_path( $case['icon_name'] );

			if ( $case['expect_null'] ) {
				$this->assertNull( $actual, $case['test_condition_name'] );
			} else {
				$this->assertNotNull( $actual, $case['test_condition_name'] );
				$this->assertFileExists( $actual, $case['test_condition_name'] );
			}
		}
	}

	/**
	 * CUSTOM_ICON_NAMES に列挙した全アイコンの SVG が実際に存在することを確認する
	 * （docs/spec.md 6.2 の 10 種と一致しているか）。
	 */
	public function test_custom_icon_names_have_svg_files() {
		$this->assertCount( 10, Badge_Icons::CUSTOM_ICON_NAMES, '独自 SVG が必要なバッジは 10 種のはず' );

		foreach ( Badge_Icons::CUSTOM_ICON_NAMES as $icon_name ) {
			$this->assertNotNull(
				Badge_Icons::custom_icon_path( $icon_name ),
				$icon_name . ' の SVG ファイルが assets/badges/ に存在するはず'
			);
		}
	}

	/**
	 * COLORS に定義したバッジ配色が付録 A の 63 種と一致することを確認する。
	 */
	public function test_colors_has_63_entries() {
		$this->assertCount( 63, Badge_Icons::COLORS, 'docs/spec.md 付録 A のバッジ配色は 63 種のはず' );
	}
}
