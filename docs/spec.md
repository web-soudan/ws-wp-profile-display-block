# WordPress.org プロフィールカードブロック 仕様書

最終更新: 2026-08-01

---

## 1. 概要・背景

### 1.1 目的

`https://profiles.wordpress.org/<username>/` のプロフィール情報（アバター・表示名・登録日・バッジ）を、**WordPress のブロックとして HTML/CSS でレンダリング**する。

### 1.2 CardPress との関係

社内では既に [CardPress（wp-profiles-card）](https://github.com/web-soudan/wp-profiles-card) を Cloudflare Workers 上で運用しており、同じプロフィールページをスクレイピングして **SVG 画像**としてカードを配信している。

本プラグインは **データ源は同じ・レンダリング方法が異なる**位置づけとなる。

| | CardPress（既存） | 本プラグイン（新規） |
|---|---|---|
| 出力形式 | SVG 画像 | HTML + CSS |
| 主な用途 | GitHub README・外部サイトへの貼り付け | WordPress サイト内への埋め込み |
| テーマ追従 | 不可 | 可（block supports） |
| テキスト選択・リンク | 不可 | 可 |
| レスポンシブ | 不可（固定サイズ） | 可 |
| 実行環境 | Cloudflare Workers（JS） | WordPress（PHP） |

CardPress のスクレイピング実装は本プラグインの**セレクタの参照元**として活用するが、実行時の依存関係は持たない（後述 1.4）。

### 1.3 非目標（やらないこと）

- SVG／画像としての出力。GitHub README 等への埋め込み用途は CardPress が引き続き担当する
- CardPress が持つ配色パラメータ UI（`headerColor` / `nameColor` 等）の再現。本プラグインは WordPress 標準の block supports に寄せる（後述 5.3）
- WordPress.org のプロフィール以外（GitHub・Slack 等）のカード化

### 1.4 アーキテクチャ上の決定

**プロフィールデータは PHP から直接スクレイピングする。** 既存の Cloudflare Worker（`/json` エンドポイント）は経由しない。

- 自己完結し、外部サービスの稼働状況に左右されない
- 反面、WordPress.org 側のマークアップ変更にはプラグイン更新で追従する必要がある（後述 12）

ただし将来的に Worker 併用へ切り替えられるよう、**正規化後のデータ構造は CardPress の `/json` 出力と互換に保つ**（後述 3.4）。

---

## 2. プラグイン基本情報

| 項目 | 値 |
|---|---|
| プラグイン名 | WS WP Profile Display Block |
| スラッグ / テキストドメイン | `ws-wp-profile-display-block` |
| ブロック名 | `ws-profile/wporg-card` |
| ライセンス | GPL v2 or later |
| 必要 WordPress バージョン | 6.5 以上（Block API v3 / `render.php` を前提とする） |
| 必要 PHP バージョン | 7.4 以上（`DOMDocument` / `DateTimeImmutable` を使用） |

### 2.1 ディレクトリ名のタイプミス（解決済み）

初期のディレクトリ名は `ws-wp-plofile-display-block`（`plofile` は `profile` の誤り）だったが、`ws-wp-profile-display-block` にリネーム済み。プラグインスラッグ・テキストドメイン・ブロック名・メインファイル名はすべてこの正しい綴りで統一している。

---

## 3. データ取得仕様

### 3.1 取得元

```
https://profiles.wordpress.org/{username}/
```

`wp_remote_get()` で取得する。

| 設定 | 値 |
|---|---|
| タイムアウト | 5 秒 |
| リダイレクト | 追従する（既定） |
| User-Agent | `ws-wp-profile-display-block/{version} (+{home_url()})` |

User-Agent には自サイトの URL を含め、WordPress.org 側から見て誰からのアクセスか分かるようにする。

> **公式 API は存在しない**
> WordPress.org にはプロフィール情報を返す公開 REST API がない（`https://profiles.wordpress.org/wp-json/wporg-profiles/v1/users/{user}` は 404 を返すことを 2026-08-01 に確認済み）。
> したがって HTML のスクレイピングが唯一の取得手段となる。

### 3.2 パース方法

`DOMDocument` + `DOMXPath` を使用する。

- `libxml_use_internal_errors( true )` を有効化し、不正な HTML でも例外を投げずに処理を続行する
- 処理後は `libxml_clear_errors()` と、元の設定への復帰を行う
- 文字コードは UTF-8 として扱う（`loadHTML()` に渡す前に `<meta charset>` を補うか、`mb_convert_encoding()` で HTML entities 化する）

### 3.3 セレクタ一覧

以下は **2026-08-01 時点の実 HTML（`profiles.wordpress.org/next-season/`）で全項目の一致を確認済み**である。

WordPress.org 側の改修時に修正箇所がここ 1 箇所へ閉じるよう、**セレクタはパーサクラス内の定数としてまとめて定義**し、コード中に散らさないこと。

| 項目 | XPath | 実例 |
|---|---|---|
| 表示名 | `//h2[contains(@class,"wp-p2-hero-name")]` | `Atsushi Ando` |
| 表示名（フォールバック） | `//header[contains(@class,"site-header")]//h2//a` → `//header[contains(@class,"site-header")]//h2` | 同上 |
| アバター | `//*[contains(@class,"wp-p2-hero-avatar")]//img/@src` | `https://secure.gravatar.com/avatar/2f48…?s=224&d=mm&r=g` |
| アバター 2x | 同 `@srcset` の `2x` 記述子を持つ URL | `…?s=448&d=mm&r=g` |
| アバター（フォールバック） | `//img[contains(@class,"avatar")]/@src` | 同上 |
| 登録日 | `//*[@id="user-member-since"]//strong` | `June 8th, 2014` |
| バッジ（各要素） | `//*[contains(@class,"wp-p2-badges-block")]//span[contains(@class,"medal")]` | — |
| └ slug | 上記要素の `@class` から `medal` を除去した残り | `badge-code` |
| └ アイコン | `.//span[contains(@class,"mi")]/@class` 内の `dashicons-[\w-]+` にマッチする部分 | `dashicons-editor-code` |
| └ 名称 | `.//span[contains(@class,"mn")]` のテキスト | `Core Contributor` |
| └ 取得年 | `.//span[contains(@class,"myear")]` のテキスト | `'15` |

参考: 実際のバッジ要素のマークアップ

```html
<span class="medal badge-code" title="Core Contributor · since December 2015">
  <span class="mi dashicons dashicons-editor-code"></span>
  <span class="mn">Core Contributor</span>
  <span class="myear">&#039;15</span>
</span>
```

#### フォールバック連鎖を必ず持たせる

WordPress.org のプロフィールページは 2026 年にリニューアルされ（`wp-p2-*` 系のクラスへ移行）、上流の donini/wp-profiles-card でも表示名とバッジのセレクタ修正が入っている（コミット `c40a988` "Fix badge and name scraping for WordPress.org profile redesign"）。

**単一セレクタに依存せず、上表のフォールバックを順に試す実装とすること。**

#### 取得失敗の判定

- **表示名が空**の場合はプロフィールとして成立していない（削除済みユーザー等）とみなし、「見つからない」扱いにする
- HTTP ステータスが 200 以外の場合も同様に「見つからない」扱いとする
- バッジ名（`.mn`）が空の要素はバッジ一覧に含めない

### 3.4 正規化後のデータ構造

CardPress の `/json` 出力とキー名を揃える（将来 Worker 併用へ切り替える際の差し替えを容易にするため）。

```php
[
	'userName'    => 'next-season',
	'name'        => 'Atsushi Ando',
	'avatar'      => 'https://secure.gravatar.com/avatar/2f48…?s=224&d=mm&r=g',
	'avatar2x'    => 'https://secure.gravatar.com/avatar/2f48…?s=448&d=mm&r=g',
	'memberSince' => 'June 8th, 2014',
	'badges'      => [
		[
			'slug' => 'badge-code',
			'name' => 'Core Contributor',
			'icon' => 'dashicons-editor-code',
			'year' => "'15",
		],
		// ...
	],
]
```

`avatar2x` / `badges[].slug` / `badges[].year` は CardPress にはない本プラグイン独自の追加項目である。

---

## 4. キャッシュと障害時の挙動

### 4.1 キャッシュ

| 項目 | 値 |
|---|---|
| 保存先 | `set_transient()` |
| キー | `ws_wporg_profile_<md5( $username )>` |
| 既定 TTL | 6 時間（21600 秒。CardPress の KV TTL と揃える） |

TTL はフィルタで上書きできるようにする。

```php
$ttl = apply_filters( 'ws_wp_profile_display_block_cache_ttl', 6 * HOUR_IN_SECONDS, $username );
```

キャッシュのデータ構造を変更した際に古いキャッシュを確実に捨てられるよう、キーにスキーマバージョンを含める（例 `ws_wporg_profile_v1_<hash>`）。

### 4.2 stale フォールバック

取得に成功したデータは、通常の transient とは別に **有効期限なしの退避コピー**（option もしくは TTL なし transient）へも保存する。

取得に失敗した場合は、この退避コピーを表示する。WordPress.org 側のマークアップ変更やダウンによって、サイト上からカードが即座に消えることを防ぐ。

判定順序:

1. 有効な transient がある → それを表示
2. 無い → 取得を試みる
   - 成功 → 表示し、transient と退避コピーの両方を更新
   - 失敗 → 退避コピーがあればそれを表示（stale）
3. 退避コピーも無い → 4.3 へ

### 4.3 何も表示できない場合

- **フロント**: 何も出力しない（エラーメッセージや空の枠を出さない）
- **エディタ**: 編集者に対してブロック内に取得失敗の旨を表示し、ユーザー名の確認を促す

### 4.4 パフォーマンス上の注意

フロントの描画中に外部への同期 HTTP リクエストが発生するのは**キャッシュミス時のみ**である。この点を実装コメントにも明記し、TTL を極端に短く設定しないよう注意喚起する。

### 4.5 アンインストール

`uninstall.php` で本プラグインが作成した transient・option をすべて削除する。

---

## 5. ブロック仕様

### 5.1 属性

| 属性 | 型 | 既定値 | CardPress 相当 | 説明 |
|---|---|---|---|---|
| `username` | string | `''` | `username` | WordPress.org のユーザー名 |
| `showAvatar` | boolean | `true` | `avatar` | アバターの表示 |
| `showHeader` | boolean | `true` | `header` | ヘッダー部（アバター・表示名・ユーザー名・登録日）の表示 |
| `showMemberSince` | boolean | `true` | — | 登録日の表示 |
| `showBadges` | boolean | `true` | `badges` | バッジ一覧の表示 |
| `badgeDisplay` | string | `label` | — | `label`（アイコン + 名称）／ `icon-only`（アイコンのみ） |
| `linkToProfile` | boolean | `true` | `link` | プロフィールページへのリンクを付与 |
| `openInNewTab` | boolean | `false` | — | リンクを新規タブで開く |

### 5.2 入力値の検証

`username` は以下のパターンで検証する（CardPress `src/lib/params.js` の `USERNAME_PATTERN` と同一）。

```
^[a-z0-9][a-z0-9._-]{0,60}$
```

- 入力は `trim()` + 小文字化してから検証する
- 検証に通らない場合は **HTTP リクエストを行わず**、フロントには何も出力しない

### 5.3 block supports

WordPress 標準の block supports を有効化し、テーマの `theme.json` に追従させる。**独自のカラーピッカーは設けない。**

```json
"supports": {
	"color": { "background": true, "text": true, "gradients": true },
	"typography": {
		"fontSize": true,
		"lineHeight": true,
		"__experimentalFontFamily": true,
		"__experimentalFontWeight": true
	},
	"spacing": { "padding": true, "margin": true, "blockGap": true },
	"__experimentalBorder": {
		"color": true, "radius": true, "style": true, "width": true
	},
	"shadow": true,
	"align": [ "wide", "full" ],
	"html": false
}
```

**角丸・影の初期値は CSS ではなく `attributes.style` の default に持たせる。** `.ws-wporg-card` に直接 `border-radius` / `box-shadow` を書くと、サイドバーの「枠線と影」パネルには何も設定されていないように見えるのに実際は角丸・影が付く、という見た目と UI の不一致が起きるため。`block.json` の `attributes.style.default` に以下を設定し、パネルに初期値として表示・編集できるようにする。

```json
"style": {
	"type": "object",
	"default": {
		"border": { "radius": "16px" },
		"shadow": "0px 1px 3px #0000000f, 0px 8px 24px #00000014"
	}
}
```

> **注意（`rgba()` を使わない理由）**：`shadow` の値は WordPress コアの Style Engine を経由して `safecss_filter_attr()`（`wp-includes/kses.php`）でサニタイズされる。この関数は `var()` / `calc()` などの既知の関数以外に含まれる丸括弧 `(` を「安全でない CSS」として値ごと**無言で破棄する**（`rgba(...)` は該当関数リストに無いため対象外）。そのため `box-shadow` の色に `rgba(0, 0, 0, 0.06)` のような値を使うと、影が全く出力されない（`get_block_wrapper_attributes()` の `style` 属性が空文字になる）。8 桁 HEX（`#RRGGBBAA`）はアルファ値を持ちながら丸括弧を含まないため、この制限を回避できる。今後 `shadow` のデフォルト値を変更する際は、`rgba()` / `hsla()` を使わず HEX + アルファで指定すること。

### 5.4 スタイルバリエーション

CardPress（画像版カード）の見た目に合わせ、アバターを左・情報を右に配置する横並びレイアウトを標準とする。バッジは円形アイコン＋ラベルの2カラムグリッドで表示する（囲み枠のピル表示ではない）。

| slug | 名称 | 内容 |
|---|---|---|
| `default` | 標準 | アバターを左、情報を右に配置。カードに背景・角丸・影を付ける |
| `stacked` | 積み上げ | アバターを上、情報を下に中央揃えで縦積み |
| `minimal` | ミニマル | カードの背景・影・パディングなし。テキストとバッジのみ |

### 5.5 レンダリング方式

**動的ブロック**とする。

- `save` は `null`（`block.json` に `"render": "file:./render.php"` を指定）
- 表示内容が外部データに依存するため、投稿内容へのシリアライズは行わない
- **エディタのプレビューは `ServerSideRender` を使用する**。フロントとエディタでマークアップを二重管理しないため

### 5.6 マークアップ方針

- ルート要素には **`get_block_wrapper_attributes()` を必ず使用する**。これがないと block supports（5.3）の設定がフロントに反映されない
- セマンティックなマークアップとする
  - アバターは `<img>`（必要に応じて `<figure>` でラップ）
  - 表示名は見出し要素ではなく `<p>` または `<span>`（カードは文書構造の一部ではないため、見出しレベルを消費しない）
  - 登録日は `<time datetime="2014-06-08">`
  - バッジ一覧は `<ul>` / `<li>`
- アバター `<img>` には `width` / `height` / `loading="lazy"` / `decoding="async"` / `srcset`（2x）を付与する
- バッジのアイコンは装飾要素のため `aria-hidden="true"` を付け、名称テキストで意味を伝える
  - `badgeDisplay` が `icon-only` の場合は、バッジ名を視覚的に隠したテキスト（`.screen-reader-text`）として保持する

---

## 6. バッジのアイコンと配色

### 6.1 アイコン

大半のバッジは **WordPress コアに同梱されている Dashicons** で描画できる。スクレイピング時に取得した `.mi` 要素の `dashicons-*` クラスをそのまま利用する。

```php
// render.php 内
wp_enqueue_style( 'dashicons' );
```

> Dashicons はフロントエンドでは未ログインユーザーに対して自動で読み込まれない。**ブロックの描画時に明示的にエンキューすること。**

### 6.2 同梱が必要な独自 SVG（10 種）

以下のバッジは対応する Dashicon が存在せず、WordPress.org 側も個別 SVG を使用している。プラグインに同梱する。

| バッジ slug |
|---|
| `badge-buddypress-contributor` |
| `badge-campus-connect-participant` |
| `badge-core-ai-contributor` |
| `badge-core-ai-team` |
| `badge-credits-graduate` |
| `badge-credits-mentor` |
| `badge-openverse-contributor` |
| `badge-performance-contributor` |
| `badge-performance-team` |
| `badge-playground-contributor` |

**移植元**: `wp-profiles-card/assets/static/images/svg/dashicons-badge-*.svg`
**ライセンス**: WordPress.org 由来のため GPL 互換。同梱にあたり出典を `readme` および該当ディレクトリの `README` に明記すること。

配置先: `assets/badges/*.svg`

### 6.3 未知のバッジ

WordPress.org 側に新しいバッジが追加され、上記いずれにも該当しない slug が来た場合は、`badge-unknown` 相当（グレー `#C7C7C7` のアウトライン）にフォールバックする。カードが壊れないことを優先する。

### 6.4 配色

CardPress の `src/lib/draw-card.js` 内の CSS に **63 種のバッジ配色**が定義されている。これを移植する。

配色は以下の 2 値で表現できる（元 CSS を分析した結果、この規則で全 63 種を再現できる）。

- **基準色** — 枠線の色、およびアイコンの色
- **塗り** — `outline`（背景は白）／ `filled`（背景は基準色の 25% 透過）

CSS カスタムプロパティで実装する。

```css
.ws-wporg-card__badge { --ws-badge-color: #C7C7C7; --ws-badge-bg: #fff; }
.ws-wporg-card__badge.badge-code { --ws-badge-color: #CD0000; }
.ws-wporg-card__badge.badge-code-committer {
	--ws-badge-color: #CD0000;
	--ws-badge-bg: color-mix( in srgb, var( --ws-badge-color ) 25%, transparent );
}
```

**全 63 種の一覧は付録 A を参照。** バッジが追加された際は付録 A と `style.scss` の両方を更新する。

---

## 7. 日付の扱い

WordPress.org は登録日を `June 8th, 2014` の形式（英語・序数付き）で出力する。

1. `DateTimeImmutable` でパースを試みる
   - 序数サフィックス（`st` / `nd` / `rd` / `th`）は事前に除去する
2. 成功 → `date_i18n()` でサイトのロケールに合わせて表示する（日本語サイトなら「2014年6月8日」）
3. 失敗 → 原文をそのまま出力する
4. パースに成功した場合は `<time datetime="2014-06-08">` を付与する

---

## 8. セキュリティ

`$VK_AGENTS_DIR/rules/coding-rules.md` の必須項目（入力値のサニタイズ・出力値のエスケープ・権限チェック）を遵守する。

- **出力エスケープ**を徹底する
  - テキスト → `esc_html()`
  - URL → `esc_url()`
  - 属性値 → `esc_attr()`
- **アバター URL の検証** — スクレイピングで得た URL をそのまま出力せず、ホスト名が `secure.gravatar.com` または `*.gravatar.com` であることを確認してから出力する
- **バッジ slug / アイコンクラスの検証** — CSS クラスとして出力するため、`[a-z0-9-]` 以外を含むものは破棄する
- **REST / AJAX を追加する場合** — `permission_callback` に `current_user_can( 'edit_posts' )` を指定する。未認証ユーザーが任意のユーザー名でスクレイピングを発火できる状態にしない
- **SSRF の防止** — 取得先 URL は `https://profiles.wordpress.org/` 固定とし、検証済みのユーザー名のみを連結する。ユーザー入力から URL 全体を組み立てない

---

## 9. ファイル構成

`coding-rules.md` に従い、**エントリポイント直下にロジックを置かない**。

また **パーサは「HTML 文字列を受け取って配列を返す純粋なメソッド」として HTTP 通信から分離**し、テスト容易性を確保する（`coding-rules.md`「テスト容易性」）。

```
ws-wp-profile-display-block/
├── ws-wp-profile-display-block.php   # エントリポイント（定数定義と読み込みのみ）
├── uninstall.php
├── readme.md
├── docs/
│   └── spec.md                       # 本ファイル
├── includes/
│   ├── class-plugin.php              # 初期化・フック登録
│   ├── class-profile-parser.php      # HTML → 配列（純粋・PHPUnit の主対象）
│   ├── class-profile-fetcher.php     # wp_remote_get による取得
│   ├── class-profile-repository.php  # transient + fetcher + parser の組み立て
│   └── class-badge-icons.php         # slug → アイコン / 配色の解決
├── src/
│   └── profile-card/
│       ├── block.json
│       ├── index.js
│       ├── edit.js
│       ├── editor.scss
│       ├── style.scss
│       └── render.php
├── build/                            # wp-scripts の出力（コミット対象）
├── assets/
│   └── badges/*.svg                  # 独自バッジ 10 種
└── tests/
    └── phpunit/
        ├── test-profile-parser.php
        ├── test-badge-icons.php
        └── fixtures/*.html
```

### 9.1 クラスの責務

| クラス | 責務 | 外部依存 |
|---|---|---|
| `Profile_Parser` | HTML 文字列 → 正規化配列 | なし（純粋） |
| `Profile_Fetcher` | ユーザー名 → HTML 文字列 | `wp_remote_get()` |
| `Profile_Repository` | ユーザー名 → 正規化配列（キャッシュ込み） | transient / 上記 2 クラス |
| `Badge_Icons` | バッジ slug → アイコンと配色 | なし（純粋） |

`Profile_Repository` は `Profile_Fetcher` を注入可能にし、テスト時にスタブへ差し替えられるようにする。

---

## 10. 国際化

- 画面に表示される文字列はすべて `__()` / `_x()` でラップし、テキストドメイン `ws-wp-profile-display-block` を指定する
- **翻訳関数には 1 文ずつ入れる。** 複数の文を 1 つの翻訳関数にまとめない
- エディタ側は `wp_set_script_translations()` で対応する
- `block.json` に `"textdomain"` を指定する

> なお、`readme.txt` が存在しないプロジェクトのため、`coding-rules.md` の判定分岐 1 により **PHPDoc・インラインコメント・ドキュメントはすべて日本語のみ**とする（英語を併記しない）。

---

## 11. テスト方針

### 11.1 PHPUnit

`coding-rules.md` により、**新規作成・変更したすべての関数／メソッドにテストを書く**。

| 対象 | テスト内容 |
|---|---|
| `Profile_Parser` | フィクスチャ HTML から表示名・アバター・登録日・バッジが正しく抽出できること |
| 同上（フォールバック） | 主セレクタが存在しない HTML でフォールバックが機能すること |
| 同上（異常系） | 表示名が取れない HTML で「見つからない」を返すこと |
| ユーザー名の検証 | 正規表現に対する正常値・異常値（大文字・記号・空文字・61 文字以上） |
| `Badge_Icons` | slug → Dashicon / 独自 SVG / `badge-unknown` フォールバックの解決 |
| 日付パース | `June 8th, 2014` → `2014-06-08`。パース不能な文字列で原文が返ること |
| `Profile_Repository` | キャッシュヒット時に fetcher が呼ばれないこと／取得失敗時に stale を返すこと |

### 11.2 フィクスチャ

実際の `profiles.wordpress.org` の HTML を保存して使用する。最低 3 パターン用意する。

1. バッジが多数あるユーザー（例: `next-season`）
2. バッジが 0 件のユーザー
3. 存在しないユーザー（404 応答）

### 11.3 静的解析・コーディング規約

- **PHPCS** — WordPress ルールセットでチェックする
- 実行は `composer install` 後に `npm run phpunit`

---

## 12. 制約と既知のリスク

### 12.1 WordPress.org のマークアップ変更で壊れる

**これが本プラグイン最大のリスクである。** 実際に 2026 年のプロフィールページリニューアルで、上流の CardPress は表示名とバッジのセレクタ修正を余儀なくされている。

以下の 3 段構えで影響を緩和する。

1. **セレクタ表**（3.3）を仕様書とコード定数の両方に持ち、修正箇所を 1 箇所に閉じる
2. **フォールバック連鎖**（3.3）で、一部のセレクタが外れても表示を維持する
3. **stale フォールバック**（4.2）で、取得が完全に失敗してもカードが消えないようにする

### 12.2 スクレイピング頻度

transient（既定 6 時間）でリクエスト頻度を抑制する。WordPress.org への負荷に配慮し、TTL を極端に短くしないこと。

### 12.3 WordPress.org での配布を検討する場合

本プラグインは外部サービス（`profiles.wordpress.org`）へ通信するため、WordPress.org のプラグインディレクトリガイドラインにより、**接続先と送信内容の開示**が `readme.txt` に必要となる。社内利用にとどめる場合は該当しない。

### 12.4 Gravatar への依存

アバター画像は `secure.gravatar.com` から直接読み込む。プライバシー要件の厳しいサイトでは、画像をローカルへキャッシュする対応が別途必要になる可能性がある（本仕様の範囲外）。

---

## 付録 A: バッジ配色一覧（63 種）

移植元: `wp-profiles-card/src/lib/draw-card.js`

- **基準色** = 枠線色 / アイコン色
- **塗り** = `outline`（背景 白）／ `filled`（背景 = 基準色の 25% 透過）

| slug | 基準色 | 塗り |
|---|---|---|
| `badge-accessibility` | `#11799D` | filled |
| `badge-accessibility-contributor` | `#11799D` | outline |
| `badge-bbpress` | `#2D8E42` | filled |
| `badge-bbpress-contributor` | `#2D8E42` | outline |
| `badge-buddypress` | `#D84800` | outline |
| `badge-buddypress-contributor` | `#D84800` | outline |
| `badge-campus-connect-participant` | `#3858E9` | outline |
| `badge-code` | `#CD0000` | outline |
| `badge-code-committer` | `#CD0000` | filled |
| `badge-community` | `#11799D` | filled |
| `badge-community-contributor` | `#11799D` | outline |
| `badge-core-ai-contributor` | `#7A00DF` | outline |
| `badge-core-ai-team` | `#7A00DF` | filled |
| `badge-credits-graduate` | `#AE47F2` | outline |
| `badge-credits-mentor` | `#8329FF` | outline |
| `badge-design` | `#EEC26A` | filled |
| `badge-design-contributor` | `#EEC26A` | outline |
| `badge-documentation` | `#3B7236` | filled |
| `badge-documentation-contributor` | `#3B7236` | outline |
| `badge-hosting` | `#5358A6` | filled |
| `badge-hosting-contributor` | `#5358A6` | outline |
| `badge-marketing` | `#47BEA7` | filled |
| `badge-marketing-contributor` | `#47BEA7` | outline |
| `badge-media-corps-contributor` | `#139F94` | outline |
| `badge-media-corps-team` | `#139F94` | outline |
| `badge-meta` | `#AEADAD` | filled |
| `badge-meta-contributor` | `#AEADAD` | outline |
| `badge-mobile` | `#FBA16C` | filled |
| `badge-openverse` | `#C52B9B` | filled |
| `badge-openverse-contributor` | `#C52B9B` | outline |
| `badge-organizer` | `#F7AD43` | outline |
| `badge-pattern-author` | `#924BB3` | outline |
| `badge-patterns-team` | `#924BB3` | filled |
| `badge-performance-contributor` | `#0073AA` | outline |
| `badge-performance-team` | `#0073AA` | filled |
| `badge-photo-contributor` | `#3B7236` | outline |
| `badge-photos-team` | `#3B7236` | filled |
| `badge-playground-contributor` | `#3858E9` | outline |
| `badge-plugins` | `#F06723` | outline |
| `badge-plugins-reviewer` | `#F06723` | filled |
| `badge-security-contributor` | `#00CC3A` | outline |
| `badge-security-team` | `#00CC3A` | filled |
| `badge-speaker` | `#F7AD43` | outline |
| `badge-support` | `#33B4CE` | filled |
| `badge-support-contributor` | `#33B4CE` | outline |
| `badge-sustainability-contributor` | `#177F6A` | outline |
| `badge-sustainability-team` | `#177F6A` | outline |
| `badge-test` | `#008080` | filled |
| `badge-test-contributor` | `#008080` | outline |
| `badge-themes` | `#4E3288` | outline |
| `badge-themes-reviewer` | `#4E3288` | filled |
| `badge-tide` | `#1526FF` | filled |
| `badge-tide-contributor` | `#1526FF` | outline |
| `badge-training` | `#E9C02D` | filled |
| `badge-training-contributor` | `#E9C02D` | outline |
| `badge-translation-contributor` | `#C32283` | outline |
| `badge-translation-editor` | `#C32283` | filled |
| `badge-unknown` | `#C7C7C7` | outline |
| `badge-wordcamp-volunteer` | `#F7AD43` | outline |
| `badge-wordpress-tv` | `#73AD30` | filled |
| `badge-wordpress-tv-contributor` | `#73AD30` | outline |
| `badge-wp-cli` | `#424242` | filled |
| `badge-wp-cli-contributor` | `#424242` | outline |

### A.1 移植時に判断が必要な箇所

移植元の CSS に不整合があり、そのまま移すと意図が曖昧になる箇所がある。

| slug | 内容 | 対応方針（要判断） |
|---|---|---|
| `badge-credits-mentor` | 元 CSS が `rgb(131, 41, 277)` で、青が 277 と範囲外。ブラウザはこの宣言を無視するため、実際には色が当たっていない | 上表では 255 にクランプして `#8329FF` としている。上流へ報告のうえ修正値を確定するのが望ましい |
| `badge-sustainability-team` | `-contributor` と同じく背景が白。他のバッジは team 側が filled になっている | 規則に合わせて `filled` にするか、元の見た目を維持するか |
| `badge-buddypress` | 同上（`-contributor` と同じ outline） | 同上 |
| `badge-media-corps-team` | 背景の指定が `background:`（SVG では無効なプロパティ）になっており効いていない | `filled` として `fill:` で実装するのが妥当 |
| `badge-security-contributor` | 背景が `unset` | `outline`（白）として扱う |
| `badge-campus-connect-participant`<br>`badge-credits-graduate`<br>`badge-credits-mentor` | 背景の指定なし | `outline` として扱う |
| `badge-core-ai-*`<br>`badge-wordcamp-volunteer` | アイコン色の指定なし | 枠線色を流用する |

---

## 付録 B: 参照した既存資産

| 内容 | パス |
|---|---|
| スクレイピング実装・セレクタの参照元 | `../wp-profiles-card/src/lib/profile.js` |
| ユーザー名の検証パターン | `../wp-profiles-card/src/lib/params.js` |
| キャッシュ TTL の根拠 | `../wp-profiles-card/src/lib/cache.js` |
| バッジ 63 種の配色 CSS | `../wp-profiles-card/src/lib/draw-card.js` |
| 独自バッジ SVG 10 種 | `../wp-profiles-card/assets/static/images/svg/dashicons-badge-*.svg` |
| JSON 出力フォーマット（互換の基準） | `../wp-profiles-card/readme.md` |
