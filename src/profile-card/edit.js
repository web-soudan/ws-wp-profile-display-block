import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
	Placeholder,
	Button,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const {
		username,
		showAvatar,
		showHeader,
		showMemberSince,
		showBadges,
		badgeDisplay,
		linkToProfile,
		openInNewTab,
	} = attributes;

	const blockProps = useBlockProps();
	const [ draftUsername, setDraftUsername ] = useState( username );

	const inspectorControls = (
		<InspectorControls>
			<PanelBody
				title={ __(
					'プロフィール設定',
					'ws-wp-profile-display-block'
				) }
			>
				<TextControl
					__nextHasNoMarginBottom
					label={ __(
						'WordPress.org ユーザー名',
						'ws-wp-profile-display-block'
					) }
					value={ username }
					onChange={ ( value ) =>
						setAttributes( { username: value } )
					}
					help={ __(
						'profiles.wordpress.org のプロフィール URL に含まれるユーザー名を入力してください。',
						'ws-wp-profile-display-block'
					) }
				/>
			</PanelBody>
			<PanelBody
				title={ __( '表示項目', 'ws-wp-profile-display-block' ) }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'ヘッダー（アバター・名前・登録日）を表示',
						'ws-wp-profile-display-block'
					) }
					checked={ showHeader }
					onChange={ ( value ) =>
						setAttributes( { showHeader: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'アバターを表示',
						'ws-wp-profile-display-block'
					) }
					checked={ showAvatar }
					onChange={ ( value ) =>
						setAttributes( { showAvatar: value } )
					}
					disabled={ ! showHeader }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'登録日を表示',
						'ws-wp-profile-display-block'
					) }
					checked={ showMemberSince }
					onChange={ ( value ) =>
						setAttributes( { showMemberSince: value } )
					}
					disabled={ ! showHeader }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'バッジを表示',
						'ws-wp-profile-display-block'
					) }
					checked={ showBadges }
					onChange={ ( value ) =>
						setAttributes( { showBadges: value } )
					}
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __(
						'バッジの表示形式',
						'ws-wp-profile-display-block'
					) }
					value={ badgeDisplay }
					options={ [
						{
							label: __(
								'アイコン + 名称',
								'ws-wp-profile-display-block'
							),
							value: 'label',
						},
						{
							label: __(
								'アイコンのみ',
								'ws-wp-profile-display-block'
							),
							value: 'icon-only',
						},
					] }
					onChange={ ( value ) =>
						setAttributes( { badgeDisplay: value } )
					}
					disabled={ ! showBadges }
				/>
			</PanelBody>
			<PanelBody title={ __( 'リンク', 'ws-wp-profile-display-block' ) }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'プロフィールページへリンク',
						'ws-wp-profile-display-block'
					) }
					checked={ linkToProfile }
					onChange={ ( value ) =>
						setAttributes( { linkToProfile: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'新しいタブで開く',
						'ws-wp-profile-display-block'
					) }
					checked={ openInNewTab }
					onChange={ ( value ) =>
						setAttributes( { openInNewTab: value } )
					}
					disabled={ ! linkToProfile }
				/>
			</PanelBody>
		</InspectorControls>
	);

	if ( ! username ) {
		return (
			<>
				{ inspectorControls }
				<div { ...blockProps }>
					<Placeholder
						icon="id-alt"
						label={ __(
							'WordPress.org プロフィールカード',
							'ws-wp-profile-display-block'
						) }
						instructions={ __(
							'WordPress.org のユーザー名を入力してください。',
							'ws-wp-profile-display-block'
						) }
					>
						<TextControl
							__nextHasNoMarginBottom
							value={ draftUsername }
							onChange={ setDraftUsername }
							placeholder={ __(
								'例: websoudan',
								'ws-wp-profile-display-block'
							) }
						/>
						<Button
							variant="primary"
							onClick={ () =>
								setAttributes( { username: draftUsername } )
							}
							disabled={ ! draftUsername }
						>
							{ __( '設定', 'ws-wp-profile-display-block' ) }
						</Button>
					</Placeholder>
				</div>
			</>
		);
	}

	return (
		<>
			{ inspectorControls }
			<div { ...blockProps }>
				<ServerSideRender
					block="ws-profile/wporg-card"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
