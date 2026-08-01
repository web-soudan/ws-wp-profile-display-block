<p align="right"><strong>日本語</strong> | <a href="README.md">English</a></p>

# WS WP Profile Display Block

[WordPress.org](https://profiles.wordpress.org/) のプロフィール(アバター・表示名・登録日・バッジ)を、静的な画像ではなく本物の HTML/CSS でレンダリングするブロックとして表示する WordPress プラグインです。

社内で運用している [CardPress](https://github.com/web-soudan/wp-profiles-card)([donini/wp-profiles-card](https://github.com/donini/wp-profiles-card) のフォーク)は同じプロフィール情報を SVG 画像として出力し、GitHub の README など外部への埋め込みに向いています。本プラグインは**同じ種類のカードを WordPress サイト内にネイティブなブロックとして表示**するもので、テーマに追従し、ダーク/ライトモードにも対応し、アクセシブルでレスポンシブです。

## 特徴

- **サーバーサイドレンダリングの動的ブロック** — サイト側にビルド手順は不要。WordPress.org の実データからその場でカードを生成します。
- **キャッシュ付きの堅牢な取得** — プロフィール情報は既定 6 時間キャッシュされ、WordPress.org が一時的に取得できない場合でも直近の取得結果にフォールバックするため、カードが突然消えることはありません。
- **63 種のバッジ配色 + 独自アイコン 10 種** — バッジの配色・アイコンは CardPress の現行実装から移植しており、profiles.wordpress.org で見た目と一致します。
- **WordPress 標準の block supports** — 色・タイポグラフィ・余白・枠線と影・配置は、ブロック標準の「スタイル」パネルからすべて編集できます。独自のカラーピッカーは持ちません。
- **3 種類のスタイルバリエーション** — `標準`(アバター左・情報右、カードに背景と影)、`積み上げ`(アバターを上、中央揃え)、`ミニマル`(カードの背景・影なし)。
- **標準でアクセシブル・安全** — 適切な alt 属性、装飾アイコンへの `aria-hidden`、出力のエスケープ、アバター URL は Gravatar のみを許可するホワイトリストなど。

## 動作要件

- WordPress 6.5 以上
- PHP 7.4 以上

## インストール

1. `wp-content/plugins/` にこのプラグインを配置します。
2. プラグイン一覧から **WS WP Profile Display Block** を有効化します。
3. ブロックエディタで「WordPress.org プロフィールカード」ブロックを挿入します(「WordPress.org」で検索)。
4. WordPress.org のユーザー名(`https://profiles.wordpress.org/<username>/` の `<username>` の部分)を入力します。

## ブロックの設定項目

| 設定 | 説明 | 既定値 |
|---|---|---|
| WORDPRESS.ORG ユーザー名 | 表示するプロフィール | (空) |
| ヘッダーを表示 | アバター・名前・ユーザー名・登録日 | オン |
| アバターを表示 | | オン |
| 登録日を表示 | | オン |
| バッジを表示 | | オン |
| バッジの表示形式 | 「アイコン + 名称」または「アイコンのみ」 | アイコン + 名称 |
| プロフィールページへリンク | カード全体を WordPress.org のプロフィールへのリンクにする | オン |
| 新しいタブで開く | | オフ |

角丸・影・色・タイポグラフィ・余白・配置は上表に含まれておらず、ブロック標準の「スタイル」パネルから編集します(いつでも既定値にリセット可能)。

## 開発

```bash
npm install
composer install
npm run build      # ブロックをビルド（src/profile-card → build/profile-card）
npx wp-env start    # ローカル WordPress を起動 http://localhost:8888 （admin / password）
```

- `composer run phpcs` / `composer run phpcbf` — WordPress Coding Standards
- `vendor/bin/phpunit --testsuite unit` — Unit テスト（WordPress 不要）
- `npx wp-env run tests-cli --env-cwd=wp-content/plugins/ws-wp-profile-display-block vendor/bin/phpunit --testsuite integration --bootstrap=tests/phpunit/bootstrap.php` — Integration テスト

スクレイピングのセレクタ・キャッシュ戦略・バッジ配色の全一覧を含む詳細仕様は [`docs/spec.md`](docs/spec.md) を参照してください。

## クレジット

- バッジの配色および独自アイコン 10 種は [wp-profiles-card](https://github.com/web-soudan/wp-profiles-card) / [donini/wp-profiles-card](https://github.com/donini/wp-profiles-card) から移植しています。
- WordPress.org および Automattic とは提携・公認関係にありません。

## ライセンス

GPL-2.0-or-later
