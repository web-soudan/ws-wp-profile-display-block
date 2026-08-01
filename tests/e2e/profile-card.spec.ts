import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'WordPress.org プロフィールカード（エディタ）', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'ユーザー名を指定してブロックを挿入するとカードが表示される', async ( { editor } ) => {
		await editor.insertBlock( {
			name: 'ws-profile/wporg-card',
			attributes: { username: 'websoudan' },
		} );

		// ServerSideRender が描画した .ws-wporg-card を対象にする。
		// エディタのブロックラッパー自身も .wp-block-ws-profile-wporg-card を持つため、
		// そちらだけで絞るとブロック本体と二重にマッチしてしまう.
		const block = editor.canvas.locator( '.ws-wporg-card.wp-block-ws-profile-wporg-card' );
		await expect( block ).toBeVisible();
		await expect( block.locator( '.ws-wporg-card__name' ) ).toHaveText( 'Webの相談所' );
		await expect( block.locator( '.ws-wporg-card__avatar' ) ).toBeVisible();
	} );

	test( 'ユーザー名が未入力の場合はプレースホルダーが表示される', async ( { editor } ) => {
		await editor.insertBlock( { name: 'ws-profile/wporg-card' } );

		await expect(
			editor.canvas.locator( '.wp-block-ws-profile-wporg-card .components-placeholder' )
		).toBeVisible();
	} );
} );

test.describe( 'WordPress.org プロフィールカード（フロント・未ログイン）', () => {
	test.use( { storageState: { cookies: [], origins: [] } } );

	test( '公開したページに本物のリンク付きでカードが表示される', async ( { page, requestUtils } ) => {
		const post = await requestUtils.createPost( {
			title: 'E2E Profile Card',
			status: 'publish',
			content: '<!-- wp:ws-profile/wporg-card {"username":"websoudan"} /-->',
		} );

		try {
			await page.goto( `/?p=${ post.id }` );

			const card = page.locator( '.wp-block-ws-profile-wporg-card' );
			await expect( card ).toBeVisible();
			await expect( card.locator( '.ws-wporg-card__name' ) ).toHaveText( 'Webの相談所' );
			await expect( card.locator( 'a.ws-wporg-card__link' ) ).toHaveAttribute(
				'href',
				'https://profiles.wordpress.org/websoudan/'
			);
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} );
		}
	} );
} );
