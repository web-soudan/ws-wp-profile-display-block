<?php
/**
 * Profile_Parser::parse() のテスト。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WS_WP_Profile_Display_Block\Profile_Parser;

/**
 * @covers \WS_WP_Profile_Display_Block\Profile_Parser
 */
class ProfileParserTest extends TestCase {

	/**
	 * フィクスチャ HTML を読み込む。
	 *
	 * @param string $filename tests/phpunit/fixtures/ 配下のファイル名。
	 * @return string HTML 文字列。
	 */
	private function load_fixture( $filename ) {
		return file_get_contents( __DIR__ . '/../fixtures/' . $filename );
	}

	/**
	 * バッジを含む実プロフィールページから、基本情報が抽出できることを確認する。
	 */
	public function test_parse() {
		$html   = $this->load_fixture( 'profile-with-badges.html' );
		$actual = Profile_Parser::parse( 'next-season', $html );

		$this->assertSame( 'next-season', $actual['userName'] );
		$this->assertSame( 'Atsushi Ando', $actual['name'] );
		$this->assertStringContainsString( 'secure.gravatar.com', $actual['avatar'] );
		$this->assertStringContainsString( 's=224', $actual['avatar'] );
		$this->assertStringContainsString( 's=448', $actual['avatar2x'] );
		$this->assertSame( 'June 8th, 2014', $actual['memberSince'] );
		$this->assertCount( 10, $actual['badges'] );
	}

	/**
	 * バッジ 1 件分のフィールド（slug / name / icon / year）が正しく分解できることを確認する。
	 */
	public function test_parse_badge_fields() {
		$html    = $this->load_fixture( 'profile-with-badges.html' );
		$actual  = Profile_Parser::parse( 'next-season', $html );
		$first   = $actual['badges'][0];

		$test_cases = array(
			array(
				'test_condition_name' => 'slug',
				'actual'               => $first['slug'],
				'expected'             => 'badge-code',
			),
			array(
				'test_condition_name' => 'name',
				'actual'               => $first['name'],
				'expected'             => 'Core Contributor',
			),
			array(
				'test_condition_name' => 'icon（コア Dashicon）',
				'actual'               => $first['icon'],
				'expected'             => 'dashicons-editor-code',
			),
			array(
				'test_condition_name' => 'year',
				'actual'               => $first['year'],
				'expected'             => "'15",
			),
		);

		foreach ( $test_cases as $case ) {
			$this->assertSame( $case['expected'], $case['actual'], $case['test_condition_name'] );
		}
	}

	/**
	 * バッジブロックが無いプロフィールでは badges が空配列になることを確認する。
	 */
	public function test_parse_with_no_badges() {
		$html   = $this->load_fixture( 'profile-no-badges.html' );
		$actual = Profile_Parser::parse( 'next-season', $html );

		$this->assertSame( 'Atsushi Ando', $actual['name'] );
		$this->assertSame( array(), $actual['badges'] );
	}

	/**
	 * 存在しないユーザーの 404 ページでは null が返ることを確認する
	 * （site-header 配下の h2「User not found」を表示名として誤検出しないこと）。
	 */
	public function test_parse_with_not_found_page() {
		$html = $this->load_fixture( 'profile-not-found.html' );

		$this->assertNull( Profile_Parser::parse( 'this-user-should-not-exist-zzz999', $html ) );
	}

	/**
	 * 表示名が取得できない HTML（空文字・関係の無い HTML 断片）では null が返ることを確認する。
	 */
	public function test_parse_with_unparseable_html() {
		$test_cases = array(
			array(
				'test_condition_name' => '空文字の HTML の場合 => null',
				'html'                 => '',
			),
			array(
				'test_condition_name' => 'プロフィールと無関係な HTML の場合 => null',
				'html'                 => '<html><body><p>Hello</p></body></html>',
			),
		);

		foreach ( $test_cases as $case ) {
			$this->assertNull( Profile_Parser::parse( 'someone', $case['html'] ), $case['test_condition_name'] );
		}
	}
}
