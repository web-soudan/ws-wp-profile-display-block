<?php
/**
 * Profile_Repository のテスト（実 WordPress 環境上で transient / option を検証する）。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block\Tests\Integration;

use WP_UnitTestCase;
use WS_WP_Profile_Display_Block\Profile_Fetcher;
use WS_WP_Profile_Display_Block\Profile_Not_Found_Exception;
use WS_WP_Profile_Display_Block\Profile_Repository;

/**
 * @covers \WS_WP_Profile_Display_Block\Profile_Repository
 */
class ProfileRepositoryTest extends WP_UnitTestCase {

	/**
	 * テストごとに transient / option をクリーンな状態にする。
	 */
	public function set_up() {
		parent::set_up();
		delete_transient( 'ws_wporg_profile_v1_' . md5( 'next-season' ) );
		delete_option( 'ws_wporg_profile_stale_v1_' . md5( 'next-season' ) );
	}

	/**
	 * フィクスチャ HTML を返す Fetcher スタブを作る。
	 *
	 * @return Profile_Fetcher&object{call_count: int} fetch() 呼び出し回数を数えるスタブ。
	 */
	private function make_success_fetcher() {
		$html = file_get_contents( dirname( __DIR__ ) . '/fixtures/profile-with-badges.html' );

		return new class( $html ) extends Profile_Fetcher {
			public $call_count = 0;
			private $html;

			public function __construct( $html ) {
				$this->html = $html;
			}

			public function fetch( $username ) {
				++$this->call_count;
				return $this->html;
			}
		};
	}

	/**
	 * 常に取得失敗する Fetcher スタブを作る。
	 *
	 * @return Profile_Fetcher
	 */
	private function make_failing_fetcher() {
		return new class() extends Profile_Fetcher {
			public function fetch( $username ) {
				throw new Profile_Not_Found_Exception( $username );
			}
		};
	}

	/**
	 * get_profile() のキャッシュ挙動のテスト。
	 */
	public function test_get_profile() {
		$fetcher    = $this->make_success_fetcher();
		$repository = new Profile_Repository( $fetcher );

		$first  = $repository->get_profile( 'next-season' );
		$second = $repository->get_profile( 'next-season' );

		$this->assertSame( 'Atsushi Ando', $first['name'], '1回目は取得したデータが返る' );
		$this->assertSame( $first, $second, '2回目はキャッシュされたデータが返る' );
		$this->assertSame( 1, $fetcher->call_count, 'キャッシュヒット時は fetch() が再度呼ばれない' );
	}

	/**
	 * refresh=true でキャッシュを無視して再取得することを確認する。
	 */
	public function test_get_profile_with_refresh() {
		$fetcher    = $this->make_success_fetcher();
		$repository = new Profile_Repository( $fetcher );

		$repository->get_profile( 'next-season' );
		$repository->get_profile( 'next-season', true );

		$this->assertSame( 2, $fetcher->call_count, 'refresh=true では毎回 fetch() が呼ばれる' );
	}

	/**
	 * 取得に成功した後、取得が失敗しても stale データにフォールバックすることを確認する。
	 */
	public function test_get_profile_falls_back_to_stale_data_on_failure() {
		$success_repository = new Profile_Repository( $this->make_success_fetcher() );
		$stale_profile       = $success_repository->get_profile( 'next-season' );

		// transient を消してキャッシュミスにし、fetch 自体は失敗させる.
		delete_transient( 'ws_wporg_profile_v1_' . md5( 'next-season' ) );

		$failing_repository = new Profile_Repository( $this->make_failing_fetcher() );
		$actual              = $failing_repository->get_profile( 'next-season' );

		$this->assertSame( $stale_profile, $actual, '取得失敗時は直近の成功データ（stale）が返る' );
	}

	/**
	 * stale データも無く取得にも失敗した場合は null が返ることを確認する。
	 */
	public function test_get_profile_returns_null_when_nothing_available() {
		$repository = new Profile_Repository( $this->make_failing_fetcher() );

		$this->assertNull( $repository->get_profile( 'someone-with-no-cache' ) );
	}
}
