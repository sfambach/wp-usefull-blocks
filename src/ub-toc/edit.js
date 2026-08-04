/**
 * Editor UI for the UB Table of Contents block.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';
import { normalizeLevelRange } from './levels';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		title = '',
		minLevel = 2,
		maxLevel = 3,
		ordered = false,
	} = attributes;

	const range = normalizeLevelRange( minLevel, maxLevel );

	const blockProps = useBlockProps( {
		className: 'ub-toc-editor',
	} );

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Title', 'wp-usefull-blocks' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						placeholder={ __( 'Contents', 'wp-usefull-blocks' ) }
						help={ __(
							'Optional heading above the list.',
							'wp-usefull-blocks'
						) }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Minimum heading level',
							'wp-usefull-blocks'
						) }
						value={ range.minLevel }
						onChange={ ( value ) => {
							const next = normalizeLevelRange(
								value,
								range.maxLevel
							);
							setAttributes( next );
						} }
						min={ 1 }
						max={ 6 }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Maximum heading level',
							'wp-usefull-blocks'
						) }
						value={ range.maxLevel }
						onChange={ ( value ) => {
							const next = normalizeLevelRange(
								range.minLevel,
								value
							);
							setAttributes( next );
						} }
						min={ 1 }
						max={ 6 }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Numbered list', 'wp-usefull-blocks' ) }
						checked={ !! ordered }
						onChange={ ( value ) =>
							setAttributes( { ordered: !! value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="ub-toc-editor__preview-label">
					{ __( 'Preview', 'wp-usefull-blocks' ) }
				</div>
				<p className="ub-toc-editor__help">
					{ __(
						'Lists headings from this post. Add Heading blocks below, then the preview updates.',
						'wp-usefull-blocks'
					) }
				</p>
				<ServerSideRender
					block="wp-usefull-blocks/ub-toc"
					attributes={ {
						...attributes,
						minLevel: range.minLevel,
						maxLevel: range.maxLevel,
					} }
				/>
			</div>
		</Fragment>
	);
}
