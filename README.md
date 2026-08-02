<p align="right"><a href="README.ja.md">日本語</a> | <strong>English</strong></p>

# WS WP Profile Display Block

A WordPress block that displays a [WordPress.org](https://profiles.wordpress.org/) profile — avatar, name, member-since date, and badges — as a native block on your site, rendered with real HTML/CSS instead of a static image.

This is a companion project to [CardPress](https://github.com/web-soudan/wp-profiles-card) (a fork of [donini/wp-profiles-card](https://github.com/donini/wp-profiles-card)), which renders the same profile data as an SVG image for embedding elsewhere (e.g. GitHub READMEs). This plugin renders the *same kind of card* natively inside WordPress, so it follows your theme, supports light/dark mode, and stays accessible and responsive.

## Features

- **Server-rendered dynamic block** — no build step required on your site; the card is generated from live WordPress.org data.
- **Cached, resilient fetching** — profile data is cached (6 hours by default) and falls back to the last known-good copy if WordPress.org is briefly unavailable, so the card never disappears from your page.
- **63 badge colors + 10 custom icons** — badge colors and icons are ported from CardPress's current rendering so the card matches what you'd see on profiles.wordpress.org.
- **Standard block supports** — color, typography, spacing, border & shadow, and alignment are all editable from the block's own **Styles** panel; no custom color pickers.
- **Three style variations** — `Default` (avatar left, info right, card background + shadow), `Stacked` (avatar on top, centered), and `Minimal` (no card background/shadow).
- **Accessible & safe by default** — proper alt text, `aria-hidden` decorative icons, escaped output, and a Gravatar-only allowlist for avatar URLs.

## Requirements

- WordPress 6.5+
- PHP 7.4+

## Installation

1. Copy this plugin into `wp-content/plugins/`.
2. Activate **WS WP Profile Display Block** from the Plugins screen.
3. In the block editor, insert the **WordPress.org プロフィールカード** block (search for "WordPress.org").
4. Enter a WordPress.org username (the one from `https://profiles.wordpress.org/<username>/`).

## Block settings

| Setting | Description | Default |
|---|---|---|
| WordPress.org username | The profile to display | *(empty)* |
| Show header | Avatar, name, username, member-since | On |
| Show avatar | | On |
| Show member-since date | | On |
| Show badges | | On |
| Badge display | `Icon + label` or `Icon only` | Icon + label |
| Link to profile | Wrap the card in a link to the WordPress.org profile | On |
| Open in new tab | | Off |

Border radius, shadow, colors, typography, spacing, and alignment are controlled from the standard block **Styles** panel (not listed above), and can be reset to the block's defaults at any time.

## Development

```bash
npm install
composer install
npm run build      # build the block (src/profile-card → build/profile-card)
npx wp-env start    # local WordPress at http://localhost:8888 (admin / password)
```

- `composer run phpcs` / `composer run phpcbf` — WordPress Coding Standards
- `vendor/bin/phpunit --testsuite unit` — unit tests (no WordPress required)
- `npx wp-env run tests-cli --env-cwd=wp-content/plugins/ws-wp-profile-display-block vendor/bin/phpunit --testsuite integration --bootstrap=tests/phpunit/bootstrap.php` — integration tests
- `npm run test:e2e` — Playwright e2e tests against the wp-env "tests" site (`:8889`). This first runs `pretest:e2e`, which activates a theme and this plugin on that site — wp-env's tests environment has no active theme by default, and without one WordPress mis-resolves plugin block script URLs (they get pointed at `wp-content/themes/default/...` instead of the plugin directory)

See [`docs/spec.md`](docs/spec.md) (Japanese) for the full specification, including the scraping selectors, caching strategy, and the full badge color table.

## Changelog

### 0.1.1

- [ Design Bug Fix ] Fixed badge names staying visible instead of being hidden from sighted users when Badge display is set to "Icon only", on sites that don't load WordPress core's default block styles
- [ Security Fix ] Added validation to discard invalid characters from badge slugs before use

## Credits

- Badge colors and the 10 custom badge icons are ported from [wp-profiles-card](https://github.com/web-soudan/wp-profiles-card) / [donini/wp-profiles-card](https://github.com/donini/wp-profiles-card).
- Not affiliated with or endorsed by WordPress.org or Automattic.

## License

GPL-2.0-or-later
