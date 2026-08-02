/**
 * Editor UI for the UB Link block.
 *
 * Behaves like a normal WordPress link (RichText + LinkControl).
 * Traffic-light status is controlled by global plugin settings.
 */

import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	LinkControl,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	Notice,
	PanelBody,
	Popover,
	ToolbarButton,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { link as linkIcon, linkOff } from '@wordpress/icons';

import StatusIndicator from '../shared/status-indicator';
import usePluginSettings from '../shared/use-plugin-settings';
import useUrlStatus from '../shared/use-url-status';

import './editor.scss';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @param {boolean}  props.isSelected
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes, isSelected } ) {
	const {
		url = '',
		label = '',
		openInNewTab = false,
		lastStatus = 'unknown',
		lastChecked = 0,
	} = attributes;

	const settings = usePluginSettings();
	const showStatus = !! settings.show_link_status;
	const autoCheck = !! settings.auto_check_urls;

	const [ isEditingURL, setIsEditingURL ] = useState( false );

	const { checking, error, checkNow } = useUrlStatus( {
		url,
		enabled: autoCheck && !! url,
		initialStatus: lastStatus,
		lastChecked,
		onStatus: ( { status, checkedAt } ) => {
			setAttributes( {
				lastStatus: status,
				lastChecked: checkedAt,
			} );
		},
	} );

	// Open the link UI when a fresh empty block is inserted.
	useEffect( () => {
		if ( isSelected && ! url ) {
			setIsEditingURL( true );
		}
	}, [ isSelected, url ] );

	const blockProps = useBlockProps( {
		className: [
			'ub-link',
			`ub-link--${ lastStatus }`,
			showStatus && lastStatus === 'broken' ? 'is-broken' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const unlink = () => {
		setAttributes( {
			url: '',
			lastStatus: 'unknown',
			lastChecked: 0,
		} );
		setIsEditingURL( true );
	};

	return (
		<>
			<BlockControls group="block">
				<ToolbarButton
					icon={ linkIcon }
					label={ __( 'Edit link', 'wp-usefull-blocks' ) }
					onClick={ () => setIsEditingURL( true ) }
					isActive={ !! url }
				/>
				{ url && (
					<ToolbarButton
						icon={ linkOff }
						label={ __( 'Unlink', 'wp-usefull-blocks' ) }
						onClick={ unlink }
					/>
				) }
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={ __( 'Link', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<p>
						{ __(
							'Status indicators and strike-through are controlled under Settings → Usefull Blocks.',
							'wp-usefull-blocks'
						) }
					</p>
					{ showStatus && (
						<p>
							{ __( 'Last status:', 'wp-usefull-blocks' ) }{ ' ' }
							<strong>{ lastStatus }</strong>
							{ checking
								? ` (${ __(
										'checking…',
										'wp-usefull-blocks'
								  ) })`
								: '' }
						</p>
					) }
					<Button
						variant="secondary"
						onClick={ () => checkNow( url ) }
						isBusy={ checking }
						disabled={ checking || ! url }
					>
						{ __( 'Check URL now', 'wp-usefull-blocks' ) }
					</Button>
					{ error && (
						<Notice
							status="error"
							isDismissible={ false }
							className="ub-link-editor__notice"
						>
							{ error }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="a"
					className="ub-link__anchor"
					value={ label }
					allowedFormats={ [] }
					withoutInteractiveFormatting
					placeholder={ __( 'Link text…', 'wp-usefull-blocks' ) }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					href={ url || undefined }
					rel={ openInNewTab ? 'noopener noreferrer' : undefined }
					target={ openInNewTab ? '_blank' : undefined }
					onClick={ ( event ) => {
						// Keep editing in the canvas; do not navigate away.
						event.preventDefault();
					} }
				/>
				{ showStatus && url && (
					<StatusIndicator status={ lastStatus } />
				) }
				{ isEditingURL && (
					<Popover
						placement="bottom-start"
						onClose={ () => setIsEditingURL( false ) }
						focusOnMount={ url ? undefined : 'firstElement' }
					>
						<LinkControl
							value={ {
								url,
								opensInNewTab: openInNewTab,
							} }
							onChange={ ( next ) => {
								const nextUrl = next?.url || '';
								setAttributes( {
									url: nextUrl,
									openInNewTab: !! next?.opensInNewTab,
									lastStatus:
										nextUrl === url
											? lastStatus
											: 'unknown',
									lastChecked:
										nextUrl === url ? lastChecked : 0,
								} );
								if ( nextUrl ) {
									setIsEditingURL( false );
								}
							} }
							onRemove={ unlink }
							hasRichPreviews
							settings={ [
								{
									id: 'opensInNewTab',
									title: __(
										'Open in new tab',
										'wp-usefull-blocks'
									),
								},
							] }
						/>
					</Popover>
				) }
			</div>
		</>
	);
}
