<?php
/**
 * Date_Formatter::parse() のテスト。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WS_WP_Profile_Display_Block\Date_Formatter;

/**
 * @covers \WS_WP_Profile_Display_Block\Date_Formatter
 */
class DateFormatterTest extends TestCase {

	/**
	 * parse() のテスト。
	 */
	public function test_parse() {
		$test_cases = array(
			array(
				'test_condition_name' => '序数サフィックス th を含む場合 => パースできる',
				'raw'                  => 'June 8th, 2014',
				'expected'             => '2014-06-08',
			),
			array(
				'test_condition_name' => '序数サフィックス st を含む場合 => パースできる',
				'raw'                  => 'January 1st, 2020',
				'expected'             => '2020-01-01',
			),
			array(
				'test_condition_name' => '序数サフィックス nd を含む場合 => パースできる',
				'raw'                  => 'March 2nd, 2021',
				'expected'             => '2021-03-02',
			),
			array(
				'test_condition_name' => '序数サフィックス rd を含む場合 => パースできる',
				'raw'                  => 'December 23rd, 1999',
				'expected'             => '1999-12-23',
			),
			array(
				'test_condition_name' => '解釈できない文字列の場合 => null',
				'raw'                  => 'not a date',
				'expected'             => null,
			),
			array(
				'test_condition_name' => '空文字の場合 => null',
				'raw'                  => '',
				'expected'             => null,
			),
		);

		foreach ( $test_cases as $case ) {
			$actual = Date_Formatter::parse( $case['raw'] );

			if ( null === $case['expected'] ) {
				$this->assertNull( $actual, $case['test_condition_name'] );
			} else {
				$this->assertSame( $case['expected'], $actual->format( 'Y-m-d' ), $case['test_condition_name'] );
			}
		}
	}
}
