/**
 * Editor UI for the UB Link block.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	Notice,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		url = '',
		label = '',
		openInNewTab = false,
		showStatus = true,
		lastStatus = 'unknown',
	} = attributes;

	const [ checking, setChecking ] = useState( false );
	const [ error, setError ] = useState( '' );

	const blockProps = useBlockProps( {
		className: 'ub-link-editor',
	} );

	const checkUrl = async () => {
		setError( '' );

		if ( ! url ) {
			setError(
				__( 'Enter a URL before checking.', 'wp-usefull-blocks' )
			);
			return;
		}

		setChecking( true );

		try {
			const result = await apiFetch( {
				path: '/wp-usefull-blocks/v1/check-url',
				method: 'POST',
				data: { url },
			} );

			setAttributes( {
				lastStatus: result?.status || 'unknown',
				lastChecked: Date.now(),
			} );
		} catch ( err ) {
			setError(
				err?.message ||
					__( 'Could not check this URL.', 'wp-usefull-blocks' )
			);
		} finally {
			setChecking( false );
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Link settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Open in new tab', 'wp-usefull-blocks' ) }
						checked={ !! openInNewTab }
						onChange={ ( value ) =>
							setAttributes( { openInNewTab: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show traffic-light status',
							'wp-usefull-blocks'
						) }
						checked={ !! showStatus }
						onChange={ ( value ) =>
							setAttributes( { showStatus: value } )
						}
					/>
					<p>
						{ __( 'Last status:', 'wp-usefull-blocks' ) }{ ' ' }
						<strong>{ lastStatus }</strong>
					</p>
					<Button
						variant="secondary"
						onClick={ checkUrl }
						isBusy={ checking }
						disabled={ checking || ! url }
					>
						{ __( 'Check URL now', 'wp-usefull-blocks' ) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'URL', 'wp-usefull-blocks' ) }
					type="url"
					value={ url }
					onChange={ ( value ) =>
						setAttributes( {
							url: value,
							lastStatus: 'unknown',
							lastChecked: 0,
						} )
					}
					placeholder="https://"
				/>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Label', 'wp-usefull-blocks' ) }
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					placeholder={ __( 'Link text', 'wp-usefull-blocks' ) }
					help={ __(
						'Leave empty to use the URL as the label.',
						'wp-usefull-blocks'
					) }
				/>
				<div className="ub-link-editor__actions">
					<Button
						variant="primary"
						onClick={ checkUrl }
						isBusy={ checking }
						disabled={ checking || ! url }
					>
						{ __( 'Check URL now', 'wp-usefull-blocks' ) }
					</Button>
				</div>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ url && (
					<>
						<div className="ub-link-editor__preview-label">
							{ __( 'Preview', 'wp-usefull-blocks' ) }
						</div>
						<ServerSideRender
							block="wp-usefull-blocks/ub-link"
							attributes={ attributes }
						/>
					</>
				) }
			</div>
		</>
	);
}
