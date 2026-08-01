<?php
/**
 * プロフィール情報のキャッシュ層。
 *
 * 2 段構えでキャッシュする。
 * 1. transient : 通常のキャッシュ（既定 6 時間）。切れたら再取得を試みる。
 * 2. stale option : 取得成功時の最新データを期限なしで退避しておく。取得が失敗した
 *    ときのフォールバックに使い、WordPress.org 側の障害やマークアップ変更でカードが
 *    即座に消えることを防ぐ。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ユーザー名からキャッシュ込みでプロフィール情報を取得するクラス。
 */
class Profile_Repository {

	/**
	 * Transient キーの接頭辞。データ構造を変えたらここのバージョンを上げてキャッシュを捨てる。
	 *
	 * @var string
	 */
	const CACHE_KEY_PREFIX = 'ws_wporg_profile_v1_';

	/**
	 * Stale フォールバック用 option キーの接頭辞。
	 *
	 * @var string
	 */
	const STALE_KEY_PREFIX = 'ws_wporg_profile_stale_v1_';

	/**
	 * 取得を担うインスタンス。
	 *
	 * @var Profile_Fetcher
	 */
	private $fetcher;

	/**
	 * コンストラクタ。
	 *
	 * @param Profile_Fetcher|null $fetcher テスト時にスタブへ差し替えるための注入ポイント。
	 */
	public function __construct( ?Profile_Fetcher $fetcher = null ) {
		$this->fetcher = $fetcher ?? new Profile_Fetcher();
	}

	/**
	 * プロフィール情報を取得する。
	 *
	 * @param string $username 検証済みのユーザー名。
	 * @param bool   $refresh  true ならキャッシュを無視して取得し直す。
	 * @return array|null 正規化済みのユーザー情報。取得もキャッシュ（stale 含む）も
	 *                     無い場合は null。
	 */
	public function get_profile( $username, $refresh = false ) {
		$cache_key = self::CACHE_KEY_PREFIX . md5( $username );

		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$profile = $this->fetch_and_parse( $username );

		if ( null !== $profile ) {
			set_transient( $cache_key, $profile, $this->get_cache_ttl( $username ) );
			update_option( self::STALE_KEY_PREFIX . md5( $username ), $profile, false );

			return $profile;
		}

		return $this->get_stale_profile( $username );
	}

	/**
	 * WordPress.org から取得してパースする。
	 *
	 * @param string $username 検証済みのユーザー名。
	 * @return array|null 成功時は正規化済みデータ、失敗時は null。
	 */
	private function fetch_and_parse( $username ) {
		try {
			$html = $this->fetcher->fetch( $username );
		} catch ( Profile_Not_Found_Exception $exception ) {
			return null;
		}

		return Profile_Parser::parse( $username, $html );
	}

	/**
	 * Stale フォールバック用に退避したプロフィール情報を取得する。
	 *
	 * @param string $username 対象のユーザー名。
	 * @return array|null 退避データ。無ければ null。
	 */
	private function get_stale_profile( $username ) {
		$stale = get_option( self::STALE_KEY_PREFIX . md5( $username ), null );

		return is_array( $stale ) ? $stale : null;
	}

	/**
	 * キャッシュの TTL（秒）を返す。
	 *
	 * @param string $username 対象のユーザー名。
	 * @return int TTL（秒）。
	 */
	private function get_cache_ttl( $username ) {
		/**
		 * プロフィールキャッシュの TTL（秒）を変更するフィルタ。
		 *
		 * @param int    $ttl      既定 6 時間。
		 * @param string $username 対象のユーザー名。
		 */
		return (int) apply_filters( 'ws_wp_profile_display_block_cache_ttl', 6 * HOUR_IN_SECONDS, $username );
	}
}
