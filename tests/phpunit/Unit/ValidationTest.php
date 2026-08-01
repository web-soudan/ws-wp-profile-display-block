<?php
/**
 * Validation::normalize_username() のテスト。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WS_WP_Profile_Display_Block\Validation;

/**
 * @covers \WS_WP_Profile_Display_Block\Validation
 */
class ValidationTest extends TestCase {

	/**
	 * normalize_username() のテスト。
	 */
	public function test_normalize_username() {
		$test_cases = array(
			array(
				'test_condition_name' => '小文字の英数字のみの場合 => そのまま返る',
				'raw'                  => 'next-season',
				'expected'             => 'next-season',
			),
			array(
				'test_condition_name' => 'ドット・アンダースコアを含む場合 => そのまま返る',
				'raw'                  => 'user.name_2',
				'expected'             => 'user.name_2',
			),
			array(
				'test_condition_name' => '大文字を含む場合 => 小文字化して返る',
				'raw'                  => 'Next-Season',
				'expected'             => 'next-season',
			),
			array(
				'test_condition_name' => '前後に空白を含む場合 => トリムして返る',
				'raw'                  => '  next-season  ',
				'expected'             => 'next-season',
			),
			array(
				'test_condition_name' => '61文字ちょうど（先頭1文字+60文字）の場合 => 許容される',
				'raw'                  => str_repeat( 'a', 61 ),
				'expected'             => str_repeat( 'a', 61 ),
			),
			array(
				'test_condition_name' => '62文字の場合 => null（境界値超過）',
				'raw'                  => str_repeat( 'a', 62 ),
				'expected'             => null,
			),
			array(
				'test_condition_name' => '空文字の場合 => null',
				'raw'                  => '',
				'expected'             => null,
			),
			array(
				'test_condition_name' => '記号（アットマーク）を含む場合 => null',
				'raw'                  => 'next@season',
				'expected'             => null,
			),
			array(
				'test_condition_name' => 'ハイフンで始まる場合 => null（先頭は英数字のみ）',
				'raw'                  => '-next-season',
				'expected'             => null,
			),
			array(
				'test_condition_name' => '文字列以外（配列）の場合 => null',
				'raw'                  => array( 'next-season' ),
				'expected'             => null,
			),
			array(
				'test_condition_name' => '文字列以外（null）の場合 => null',
				'raw'                  => null,
				'expected'             => null,
			),
		);

		foreach ( $test_cases as $case ) {
			$actual = Validation::normalize_username( $case['raw'] );

			$this->assertSame( $case['expected'], $actual, $case['test_condition_name'] );
		}
	}
}
