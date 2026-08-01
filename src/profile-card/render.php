<?php
/**
 * プロフィールカードブロックの動的レンダリング。
 *
 * @package WS_WP_Profile_Display_Block
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    内包ブロックの HTML（このブロックは内包ブロックを持たない）。
 * @var WP_Block $block      ブロックインスタンス。
 */

namespace WS_WP_Profile_Display_Block;

// 直接アクセスを禁止する.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$username = Validation::normalize_username( $attributes['username'] ?? '' );

// ユーザー名が未入力・不正な場合は外部への通信を一切行わない.
if ( null === $username ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		printf(
			'<p %1$s>%2$s</p>',
			get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() は既にエスケープ済みの属性文字列を返す.
			esc_html__( 'WordPress.org のユーザー名を入力してください。', 'ws-wp-profile-display-block' )
		);
	}

	return;
}

$repository = new Profile_Repository();
$profile    = $repository->get_profile( $username );

if ( null === $profile ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		printf(
			'<p %1$s>%2$s</p>',
			get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() は既にエスケープ済みの属性文字列を返す.
			esc_html__( 'プロフィールを取得できませんでした。ユーザー名を確認してください。', 'ws-wp-profile-display-block' )
		);
	}

	return;
}

$show_avatar       = ! empty( $attributes['showAvatar'] );
$show_header       = ! empty( $attributes['showHeader'] );
$show_member_since = ! empty( $attributes['showMemberSince'] );
$show_badges       = ! empty( $attributes['showBadges'] );
$link_to_profile   = ! empty( $attributes['linkToProfile'] );
$open_in_new_tab   = ! empty( $attributes['openInNewTab'] );
$icon_only         = 'icon-only' === ( $attributes['badgeDisplay'] ?? 'label' );

/*
 * ブロックエディタのプレビュー（ServerSideRender 経由の REST リクエスト）では
 * 本物の <a href> を出さない。ServerSideRender が返す HTML はそのままブロック
 * キャンバス（iframe）に描き込まれ、クリックイベントを横取りする仕組みが無いため、
 * ブロックを選択しようとしてこのリンク上をクリックすると iframe ごと
 * https://profiles.wordpress.org/ へ本物のページ遷移をしてしまい、X-Frame-Options
 * によりブロックされてエディタ全体がクラッシュする（2026-08-01 に実機で確認済み）。
 * 見た目を変えずにリンクの危険性だけを取り除くため、同じクラス名の <div> に差し替える。
 */
$is_editor_preview  = defined( 'REST_REQUEST' ) && REST_REQUEST;
$render_link_anchor = $link_to_profile && ! $is_editor_preview;

$profile_url = 'https://profiles.wordpress.org/' . rawurlencode( $profile['userName'] ) . '/';

/**
 * アバター URL が信頼できるホスト（Gravatar）かどうかを判定する。
 *
 * @param string $url 判定対象の URL。
 * @return bool 出力してよければ true。
 */
$is_trusted_avatar_host = static function ( $url ) {
	if ( '' === $url ) {
		return false;
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );

	return is_string( $host ) && 1 === preg_match( '/(^|\.)gravatar\.com$/i', $host );
};

if ( $show_avatar && ! $is_trusted_avatar_host( $profile['avatar'] ) ) {
	$show_avatar = false;
}

$formatted_member_since = $profile['memberSince'];
$member_since_datetime  = '';
$parsed_date            = Date_Formatter::parse( $profile['memberSince'] );

if ( null !== $parsed_date ) {
	$formatted_member_since = date_i18n( get_option( 'date_format' ), $parsed_date->getTimestamp() );
	$member_since_datetime  = $parsed_date->format( 'Y-m-d' );
}

/**
 * バッジのアイコン部分の HTML を組み立てる。
 *
 * @param array $badge バッジ 1 件分のデータ（slug / name / icon / year）。
 * @return string アイコンの HTML。
 */
$render_badge_icon = static function ( $badge ) {
	$icon_name = $badge['icon'];

	if ( Badge_Icons::is_custom_icon( $icon_name ) ) {
		$path = Badge_Icons::custom_icon_path( $icon_name );

		if ( null !== $path ) {
			$svg = wp_kses(
				file_get_contents( $path ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- プラグイン同梱の固定 SVG をローカルから読むだけで、リモート URL や外部/ユーザー入力ではない.
				array(
					'svg'  => array(
						'viewbox'     => true,
						'viewBox'     => true,
						'xmlns'       => true,
						'class'       => true,
						'aria-hidden' => true,
						'focusable'   => true,
					),
					'path' => array( 'd' => true ),
				)
			);

			return '<span class="ws-wporg-card__badge-icon" aria-hidden="true">' . $svg . '</span>';
		}
	}

	return sprintf(
		'<span class="ws-wporg-card__badge-icon dashicons %s" aria-hidden="true"></span>',
		esc_attr( $icon_name )
	);
};

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'ws-wporg-card' ) );
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() は既にエスケープ済みの属性文字列を返す. ?>>
	<?php if ( $render_link_anchor ) : ?>
		<a
			class="ws-wporg-card__link"
			href="<?php echo esc_url( $profile_url ); ?>"
			<?php echo $open_in_new_tab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
		>
	<?php elseif ( $link_to_profile ) : ?>
		<div class="ws-wporg-card__link">
	<?php endif; ?>

	<?php if ( $show_header ) : ?>
		<div class="ws-wporg-card__header">
			<?php if ( $show_avatar ) : ?>
				<img
					class="ws-wporg-card__avatar"
					src="<?php echo esc_url( $profile['avatar'] ); ?>"
					<?php if ( '' !== $profile['avatar2x'] && $is_trusted_avatar_host( $profile['avatar2x'] ) ) : ?>
						srcset="<?php echo esc_url( $profile['avatar2x'] ); ?> 2x"
					<?php endif; ?>
					width="112"
					height="112"
					loading="lazy"
					decoding="async"
					alt=""
				/>
			<?php endif; ?>

			<div class="ws-wporg-card__identity">
				<p class="ws-wporg-card__name"><?php echo esc_html( $profile['name'] ); ?></p>
				<p class="ws-wporg-card__username">@<?php echo esc_html( $profile['userName'] ); ?></p>

				<?php if ( $show_member_since && '' !== $profile['memberSince'] ) : ?>
					<p class="ws-wporg-card__member-since">
						<?php esc_html_e( '登録日:', 'ws-wp-profile-display-block' ); ?>
						<?php if ( '' !== $member_since_datetime ) : ?>
							<time datetime="<?php echo esc_attr( $member_since_datetime ); ?>"><?php echo esc_html( $formatted_member_since ); ?></time>
						<?php else : ?>
							<?php echo esc_html( $formatted_member_since ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $render_link_anchor ) : ?>
		</a>
	<?php elseif ( $link_to_profile ) : ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_badges && ! empty( $profile['badges'] ) ) : ?>
		<ul class="ws-wporg-card__badges">
			<?php foreach ( $profile['badges'] as $badge ) : ?>
				<?php
				$badge_color      = Badge_Icons::get_color( $badge['slug'] );
				$badge_filled     = Badge_Icons::is_filled( $badge['slug'] );
				$badge_bg         = $badge_filled ? $badge_color : '#ffffff';
				$badge_icon_color = $badge_filled ? '#ffffff' : $badge_color;
				?>
				<li
					class="ws-wporg-card__badge <?php echo esc_attr( $badge['slug'] ); ?>"
					style="--ws-badge-color: <?php echo esc_attr( $badge_color ); ?>; --ws-badge-bg: <?php echo esc_attr( $badge_bg ); ?>; --ws-badge-icon-color: <?php echo esc_attr( $badge_icon_color ); ?>;"
					title="<?php echo esc_attr( $badge['name'] ); ?>"
				>
					<span class="ws-wporg-card__badge-circle">
						<?php echo $render_badge_icon( $badge ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $render_badge_icon() 内でエスケープ・サニタイズ済み. ?>
					</span>
					<?php if ( $icon_only ) : ?>
						<span class="screen-reader-text"><?php echo esc_html( $badge['name'] ); ?></span>
					<?php else : ?>
						<span class="ws-wporg-card__badge-name"><?php echo esc_html( $badge['name'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
