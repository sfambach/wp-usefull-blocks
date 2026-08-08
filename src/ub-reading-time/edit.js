/**
 * Editor UI for the UB Reading Time block.
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
import { MAX_WPM, MIN_WPM, normalizeWpm } from './wpm';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		wordsPerMinute = 200,
		showWordCount = false,
		prefix = '',
	} = attributes;

	const wpm = normalizeWpm( wordsPerMinute );

	const blockProps = useBlockProps( {
		className: 'ub-reading-time-editor',
	} );

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Words per minute', 'wp-usefull-blocks' ) }
						value={ wpm }
						onChange={ ( value ) =>
							setAttributes( {
								wordsPerMinute: normalizeWpm( value ),
							} )
						}
						min={ MIN_WPM }
						max={ MAX_WPM }
						step={ 10 }
						help={ __(
							'Average adult reading speed is about 200–250 WPM.',
							'wp-usefull-blocks'
						) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show word count', 'wp-usefull-blocks' ) }
						checked={ !! showWordCount }
						onChange={ ( value ) =>
							setAttributes( { showWordCount: !! value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Prefix label', 'wp-usefull-blocks' ) }
						value={ prefix }
						onChange={ ( value ) =>
							setAttributes( { prefix: value } )
						}
						placeholder={ __(
							'Optional, e.g. Reading time:',
							'wp-usefull-blocks'
						) }
						help={ __(
							'Shown before the estimate. Leave empty for the default phrasing only.',
							'wp-usefull-blocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="ub-reading-time-editor__preview-label">
					{ __( 'Preview', 'wp-usefull-blocks' ) }
				</div>
				<p className="ub-reading-time-editor__help">
					{ __(
						'Calculated from this post’s content. Add body text, then the preview updates.',
						'wp-usefull-blocks'
					) }
				</p>
				<ServerSideRender
					block="wp-usefull-blocks/ub-reading-time"
					attributes={ {
						...attributes,
						wordsPerMinute: wpm,
					} }
				/>
			</div>
		</Fragment>
	);
}
