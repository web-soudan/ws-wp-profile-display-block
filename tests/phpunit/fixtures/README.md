# フィクスチャの出典

いずれも `https://profiles.wordpress.org/` から 2026-08-01 に取得した実際の HTML の抜粋。

- `profile-with-badges.html` — `next-season` のプロフィールページから、ヘッダー（`site-header`）・バッジブロック（`wp-p2-badges-block`）・登録日（`user-member-since`）を抜粋したもの。バッジ 10 件を含む。
- `profile-no-badges.html` — 同じく `next-season` のヘッダーと登録日のみを抜粋し、バッジブロックを除いたもの（バッジを持たないユーザーの構造を模している。実際にバッジ 0 件のアカウントを確認できたものではない点に注意）。
- `profile-not-found.html` — 存在しないユーザー名（`this-user-should-not-exist-zzz999`）へのアクセスで返る WordPress.org 標準の 404 ページ全文。
