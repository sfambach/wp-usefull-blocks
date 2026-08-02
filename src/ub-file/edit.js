/**
 * Editor UI for the UB File block.
 *
 * Mirroring is manual: the author presses "Download now".
 * Traffic-light status follows global plugin settings.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	Notice,
	PanelBody,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';

import StatusIndicator from '../shared/status-indicator';
import usePluginSettings from '../shared/use-plugin-settings';
import useUrlStatus from '../shared/use-url-status';

import './editor.scss';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		sourceUrl = '',
		label = '',
		attachmentId = 0,
		mirroredFromUrl = '',
		linkBehavior = 'original-fallback-local',
		lastStatus = 'unknown',
	} = attributes;

	const settings = usePluginSettings();
	const showStatus = !! settings.show_link_status;
	const autoCheck = !! settings.auto_check_urls;

	const [ busy, setBusy ] = useState( '' );
	const [ message, setMessage ] = useState( '' );
	const [ error, setError ] = useState( '' );

	const {
		checking,
		error: checkError,
		checkNow,
	} = useUrlStatus( {
		url: sourceUrl,
		enabled: autoCheck && !! sourceUrl,
		initialStatus: lastStatus,
		onStatus: ( { status, checkedAt } ) => {
			setAttributes( {
				lastStatus: status,
				lastChecked: checkedAt,
			} );
		},
	} );

	const needsRedownload =
		!! attachmentId &&
		!! sourceUrl &&
		mirroredFromUrl &&
		mirroredFromUrl !== sourceUrl;

	const blockProps = useBlockProps( {
		className: 'ub-file-editor',
	} );

	const downloadNow = async () => {
		setError( '' );
		setMessage( '' );

		if ( ! sourceUrl ) {
			setError( __( 'Enter a file URL first.', 'wp-usefull-blocks' ) );
			return;
		}

		setBusy( 'download' );

		try {
			const result = await apiFetch( {
				path: '/wp-usefull-blocks/v1/file-mirror',
				method: 'POST',
				data: { url: sourceUrl },
			} );

			setAttributes( {
				attachmentId: result?.attachmentId || 0,
				mirroredFromUrl: sourceUrl,
			} );
			setMessage(
				__(
					'File downloaded into the media library.',
					'wp-usefull-blocks'
				)
			);
		} catch ( err ) {
			setError(
				err?.message ||
					__(
						'Download failed. Check the URL and permissions.',
						'wp-usefull-blocks'
					)
			);
		} finally {
			setBusy( '' );
		}
	};

	const runCheck = async () => {
		setError( '' );
		setMessage( '' );
		const result = await checkNow( sourceUrl );
		if ( result ) {
			setMessage( sprintfStatus( result?.status, result?.code ) );
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'File settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Link behavior', 'wp-usefull-blocks' ) }
						value={ linkBehavior }
						options={ [
							{
								label: __(
									'Original, fallback to local copy',
									'wp-usefull-blocks'
								),
								value: 'original-fallback-local',
							},
							{
								label: __(
									'Original only',
									'wp-usefull-blocks'
								),
								value: 'original-only',
							},
							{
								label: __(
									'Local copy only',
									'wp-usefull-blocks'
								),
								value: 'local-only',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { linkBehavior: value } )
						}
					/>
					<p>
						{ __(
							'Status indicators and strike-through are controlled under Settings → Usefull Blocks.',
							'wp-usefull-blocks'
						) }
					</p>
					<p>
						{ __( 'Media attachment ID:', 'wp-usefull-blocks' ) }{ ' ' }
						<strong>{ attachmentId || '—' }</strong>
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
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Mirroring is manual. Press “Download now” to copy the file into the media library. If you change the URL later, press Download now again.',
						'wp-usefull-blocks'
					) }
				</Notice>

				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'File URL', 'wp-usefull-blocks' ) }
					type="url"
					value={ sourceUrl }
					onChange={ ( value ) =>
						setAttributes( {
							sourceUrl: value,
							lastStatus: 'unknown',
							lastChecked: 0,
						} )
					}
					placeholder="https://example.com/file.pdf"
				/>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Link label', 'wp-usefull-blocks' ) }
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					placeholder={ __( 'Download file', 'wp-usefull-blocks' ) }
				/>

				{ needsRedownload && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'The URL changed since the last download. Press Download now to refresh the local copy.',
							'wp-usefull-blocks'
						) }
					</Notice>
				) }

				<div className="ub-file-editor__actions">
					<Button
						variant="primary"
						onClick={ downloadNow }
						isBusy={ 'download' === busy }
						disabled={ !! busy || ! sourceUrl }
					>
						{ attachmentId
							? __(
									'Download now (refresh)',
									'wp-usefull-blocks'
							  )
							: __( 'Download now', 'wp-usefull-blocks' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ runCheck }
						isBusy={ checking }
						disabled={ !! busy || checking || ! sourceUrl }
					>
						{ __( 'Check URL now', 'wp-usefull-blocks' ) }
					</Button>
					{ showStatus && sourceUrl && (
						<StatusIndicator status={ lastStatus } />
					) }
				</div>

				{ message && (
					<Notice status="success" isDismissible={ false }>
						{ message }
					</Notice>
				) }
				{ ( error || checkError ) && (
					<Notice status="error" isDismissible={ false }>
						{ error || checkError }
					</Notice>
				) }

				{ sourceUrl && (
					<>
						<div className="ub-file-editor__preview-label">
							{ __( 'Preview', 'wp-usefull-blocks' ) }
						</div>
						<ServerSideRender
							block="wp-usefull-blocks/ub-file"
							attributes={ attributes }
						/>
					</>
				) }
			</div>
		</>
	);
}

/**
 * @param {string} status Status key.
 * @param {number} code   HTTP code.
 * @return {string} Message.
 */
function sprintfStatus( status, code ) {
	if ( 'ok' === status ) {
		return __( 'URL looks OK.', 'wp-usefull-blocks' ) + ` (${ code })`;
	}
	if ( 'broken' === status ) {
		return __( 'URL looks broken.', 'wp-usefull-blocks' ) + ` (${ code })`;
	}
	return (
		__( 'URL status is unclear — check manually.', 'wp-usefull-blocks' ) +
		` (${ code })`
	);
}
