<?php
/**
 * WordPress.org のプロフィールページ HTML を解析する。
 *
 * ここで扱うセレクタは docs/spec.md の「3.3 セレクタ一覧」と一対一で対応させる。
 * WordPress.org 側にマークアップ変更が入った場合は、この表とここのメソッドだけを
 * 直せば追従できるようにしておくこと。単一セレクタに依存せず、フォールバックを持つ。
 *
 * @package WS_WP_Profile_Display_Block
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML 文字列 → 正規化配列 の変換を担う純粋なクラス（外部依存なし）。
 */
class Profile_Parser {

	/**
	 * プロフィールページの HTML を解析する。
	 *
	 * @param string $username 対象のユーザー名（結果にそのまま格納する）。
	 * @param string $html     プロフィールページの HTML。
	 * @return array|null 正規化済みのユーザー情報。表示名が取得できない場合は null
	 *                     （削除済みユーザーなど、プロフィールとして成立していない）。
	 */
	public static function parse( $username, $html ) {
		$xpath = self::create_xpath( $html );

		// 存在しないユーザー名では WordPress.org 標準の 404 ページ
		// （<body class="… error404 …"><header class="site-header clear"><h2 class="posttitle">
		// User not found</h2></header>…）が返る。この h2 は 3.3 節の名前フォールバックに
		// マッチしてしまう（class に site-header を含む header 配下の h2 のため）ので、
		// 誤検出を避けるために先に弾く。
		if ( self::is_not_found_page( $xpath ) ) {
			return null;
		}

		$name = self::extract_name( $xpath );

		if ( '' === $name ) {
			return null;
		}

		list( $avatar, $avatar_2x ) = self::extract_avatar( $xpath );

		return array(
			'userName'    => $username,
			'name'        => $name,
			'avatar'      => $avatar,
			'avatar2x'    => $avatar_2x,
			'memberSince' => self::extract_member_since( $xpath ),
			'badges'      => self::extract_badges( $xpath ),
		);
	}

	/**
	 * HTML 文字列から DOMXPath を組み立てる。
	 *
	 * @param string $html プロフィールページの HTML。
	 * @return \DOMXPath
	 */
	private static function create_xpath( $html ) {
		$previous_setting = libxml_use_internal_errors( true );

		$document = new \DOMDocument();
		// UTF-8 の文字化けを避けるため、文字コード宣言を明示的に補ってから読み込む.
		$document->loadHTML(
			'<?xml encoding="UTF-8">' . $html,
			LIBXML_NOERROR | LIBXML_NOWARNING
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_setting );

		return new \DOMXPath( $document );
	}

	/**
	 * WordPress.org 標準の「ユーザーが見つかりません」ページかどうかを判定する。
	 *
	 * 実際のページでは <body> や周辺要素に "error404" "page-404" のクラスが付く
	 * （2026-08-01 確認）。
	 *
	 * @param \DOMXPath $xpath 対象ページの DOMXPath。
	 * @return bool 404 ページとみなせるなら true。
	 */
	private static function is_not_found_page( \DOMXPath $xpath ) {
		$nodes = $xpath->query(
			'//*[contains(concat(" ", normalize-space(@class), " "), " error404 ")' .
			' or contains(concat(" ", normalize-space(@class), " "), " page-404 ")]'
		);

		return (bool) ( $nodes && $nodes->length > 0 );
	}

	/**
	 * 表示名を抽出する。
	 *
	 * @param \DOMXPath $xpath 対象ページの DOMXPath。
	 * @return string 表示名。取得できなければ空文字。
	 */
	private static function extract_name( \DOMXPath $xpath ) {
		$queries = array(
			'//h2[contains(concat(" ", normalize-space(@class), " "), " wp-p2-hero-name ")]',
			'//header[contains(concat(" ", normalize-space(@class), " "), " site-header ")]//h2//a',
			'//header[contains(concat(" ", normalize-space(@class), " "), " site-header ")]//h2',
		);

		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );

			if ( $nodes && $nodes->length > 0 ) {
				$text = trim( $nodes->item( 0 )->textContent );

				if ( '' !== $text ) {
					return $text;
				}
			}
		}

		return '';
	}

	/**
	 * アバター画像の URL（等倍・2倍）を抽出する。
	 *
	 * @param \DOMXPath $xpath 対象ページの DOMXPath。
	 * @return array{0: string, 1: string} [ 等倍URL, 2倍URL ]。取得できなければ空文字。
	 */
	private static function extract_avatar( \DOMXPath $xpath ) {
		$queries = array(
			'//*[contains(concat(" ", normalize-space(@class), " "), " wp-p2-hero-avatar ")]//img',
			'//img[contains(concat(" ", normalize-space(@class), " "), " avatar ")]',
		);

		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );

			if ( ! $nodes || 0 === $nodes->length ) {
				continue;
			}

			/**
			 * アバター画像要素。
			 *
			 * @var \DOMElement $img
			 */
			$img = $nodes->item( 0 );
			$src = trim( (string) $img->getAttribute( 'src' ) );

			if ( '' === $src ) {
				continue;
			}

			return array( $src, self::extract_srcset_2x( $img->getAttribute( 'srcset' ) ) );
		}

		return array( '', '' );
	}

	/**
	 * Srcset 属性から 2x 記述子の URL を取り出す。
	 *
	 * @param string $srcset srcset 属性の値（例 "https://…?s=448 2x"）。
	 * @return string 2x の URL。無ければ空文字。
	 */
	private static function extract_srcset_2x( $srcset ) {
		if ( '' === trim( (string) $srcset ) ) {
			return '';
		}

		foreach ( explode( ',', $srcset ) as $candidate ) {
			$parts = preg_split( '/\s+/', trim( $candidate ) );

			if ( isset( $parts[1] ) && '2x' === $parts[1] ) {
				return $parts[0];
			}
		}

		return '';
	}

	/**
	 * 登録日（Member Since）を抽出する。
	 *
	 * @param \DOMXPath $xpath 対象ページの DOMXPath。
	 * @return string 登録日の原文（例 "June 8th, 2014"）。取得できなければ空文字。
	 */
	private static function extract_member_since( \DOMXPath $xpath ) {
		$nodes = $xpath->query( '//*[@id="user-member-since"]//strong' );

		if ( $nodes && $nodes->length > 0 ) {
			return trim( $nodes->item( 0 )->textContent );
		}

		return '';
	}

	/**
	 * バッジ一覧を抽出する。
	 *
	 * @param \DOMXPath $xpath 対象ページの DOMXPath。
	 * @return array<int, array{slug: string, name: string, icon: string, year: string}>
	 */
	private static function extract_badges( \DOMXPath $xpath ) {
		$badge_nodes = $xpath->query(
			'//*[contains(concat(" ", normalize-space(@class), " "), " wp-p2-badges-block ")]' .
			'//span[contains(concat(" ", normalize-space(@class), " "), " medal ")]'
		);

		$badges = array();

		if ( ! $badge_nodes ) {
			return $badges;
		}

		foreach ( $badge_nodes as $badge_node ) {
			/**
			 * バッジ要素。
			 *
			 * @var \DOMElement $badge_node
			 */
			$name = self::extract_badge_field( $xpath, $badge_node, 'mn' );

			// バッジ名が取れない要素はカードに含めない（構造が崩れている等）.
			if ( '' === $name ) {
				continue;
			}

			$slug = self::extract_badge_slug( $badge_node );
			$icon = self::extract_badge_icon( $xpath, $badge_node, $slug );
			$year = self::extract_badge_field( $xpath, $badge_node, 'myear' );

			$badges[] = array(
				'slug' => $slug,
				'name' => $name,
				'icon' => $icon,
				'year' => $year,
			);
		}

		return $badges;
	}

	/**
	 * バッジ要素の class から "medal" を除いた slug を取り出す。
	 *
	 * slug は CSS クラス名・style 属性のキーとして出力に使われるため、
	 * `[a-z0-9-]` 以外を含むトークンは破棄する（docs/spec.md 8 参照）。
	 *
	 * @param \DOMElement $badge_node バッジ要素（.medal）。
	 * @return string バッジ slug（例 "badge-code"）。
	 */
	private static function extract_badge_slug( \DOMElement $badge_node ) {
		$classes = preg_split( '/\s+/', trim( (string) $badge_node->getAttribute( 'class' ) ) );
		$classes = array_filter(
			$classes,
			static function ( $class_name ) {
				return 'medal' !== $class_name && 1 === preg_match( '/^[a-z0-9-]+$/', $class_name );
			}
		);

		return implode( ' ', $classes );
	}

	/**
	 * バッジ要素配下の子要素（.mn / .myear など）のテキストを取り出す。
	 *
	 * @param \DOMXPath   $xpath      対象ページの DOMXPath。
	 * @param \DOMElement $badge_node バッジ要素。
	 * @param string      $class_name 取り出したい子要素のクラス名（"medal" は含めない単純な名前）。
	 * @return string テキスト。取得できなければ空文字。
	 */
	private static function extract_badge_field( \DOMXPath $xpath, \DOMElement $badge_node, $class_name ) {
		$nodes = $xpath->query(
			'.//span[contains(concat(" ", normalize-space(@class), " "), " ' . $class_name . ' ")]',
			$badge_node
		);

		if ( $nodes && $nodes->length > 0 ) {
			return trim( $nodes->item( 0 )->textContent );
		}

		return '';
	}

	/**
	 * バッジのアイコン名を抽出する。
	 *
	 * `.mi` 要素の class に `dashicons-xxx` があればそれを使う（WordPress コアの Dashicon）。
	 * 無ければ `dashicons-{badgeSlug}` にフォールバックする（独自 SVG のファイル名と一致する
	 * 命名規則。移植元 wp-profiles-card の processCard() と同じロジック）。
	 *
	 * @param \DOMXPath   $xpath      対象ページの DOMXPath。
	 * @param \DOMElement $badge_node バッジ要素。
	 * @param string      $badge_slug 3引数目の extract_badge_slug() の戻り値。
	 * @return string アイコン名（例 "dashicons-editor-code" / "dashicons-badge-core-ai-team"）。
	 */
	private static function extract_badge_icon( \DOMXPath $xpath, \DOMElement $badge_node, $badge_slug ) {
		$nodes = $xpath->query(
			'.//span[contains(concat(" ", normalize-space(@class), " "), " mi ")]',
			$badge_node
		);

		if ( $nodes && $nodes->length > 0 ) {
			/**
			 * アイコン要素。
			 *
			 * @var \DOMElement $mi
			 */
			$mi = $nodes->item( 0 );

			if ( 1 === preg_match( '/dashicons-[\w-]+/', (string) $mi->getAttribute( 'class' ), $matches ) ) {
				return $matches[0];
			}
		}

		return 'dashicons-' . $badge_slug;
	}
}
