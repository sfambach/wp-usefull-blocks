/**
 * Editor UI for the UB Callout block.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';
import { normalizeVariant } from './variants';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		variant = 'info',
		title = '',
		content = '',
		showIcon = true,
	} = attributes;

	const safeVariant = normalizeVariant( variant );
	const hasPreview = '' !== title.trim() || '' !== content.trim();

	const blockProps = useBlockProps( {
		className: 'ub-callout-editor',
	} );

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Type', 'wp-usefull-blocks' ) }
						value={ safeVariant }
						options={ [
							{
								label: __( 'Info', 'wp-usefull-blocks' ),
								value: 'info',
							},
							{
								label: __( 'Tip', 'wp-usefull-blocks' ),
								value: 'tip',
							},
							{
								label: __( 'Warning', 'wp-usefull-blocks' ),
								value: 'warning',
							},
							{
								label: __( 'Success', 'wp-usefull-blocks' ),
								value: 'success',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								variant: normalizeVariant( value ),
							} )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show icon', 'wp-usefull-blocks' ) }
						checked={ !! showIcon }
						onChange={ ( value ) =>
							setAttributes( { showIcon: !! value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="ub-callout-editor__fields">
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Title', 'wp-usefull-blocks' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						placeholder={ __(
							'Optional title…',
							'wp-usefull-blocks'
						) }
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Message', 'wp-usefull-blocks' ) }
						value={ content }
						onChange={ ( value ) =>
							setAttributes( { content: value } )
						}
						placeholder={ __(
							'Write the callout message…',
							'wp-usefull-blocks'
						) }
						rows={ 4 }
					/>
				</div>

				<div className="ub-callout-editor__preview">
					<div className="ub-callout-editor__preview-label">
						{ __( 'Preview', 'wp-usefull-blocks' ) }
					</div>
					{ hasPreview ? (
						<ServerSideRender
							block="wp-usefull-blocks/ub-callout"
							attributes={ {
								...attributes,
								variant: safeVariant,
							} }
						/>
					) : (
						<p className="ub-callout-editor__preview-empty">
							{ __(
								'Add a title or message to preview the callout.',
								'wp-usefull-blocks'
							) }
						</p>
					) }
				</div>
			</div>
		</Fragment>
	);
}
