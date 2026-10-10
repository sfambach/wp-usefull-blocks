/**
 * Editor UI for the UB Post Tags block.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		expandInline = true,
		postsPerTag = 10,
		showCount = true,
	} = attributes;

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show posts below the tag',
							'wp-usefull-blocks'
						) }
						help={ __(
							'Off: a click opens the tag archive page.',
							'wp-usefull-blocks'
						) }
						checked={ !! expandInline }
						onChange={ ( value ) =>
							setAttributes( { expandInline: !! value } )
						}
					/>
					{ expandInline && (
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Posts per tag', 'wp-usefull-blocks' ) }
							value={ postsPerTag }
							onChange={ ( value ) =>
								setAttributes( { postsPerTag: value || 10 } )
							}
							min={ 1 }
							max={ 30 }
						/>
					) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show post count', 'wp-usefull-blocks' ) }
						checked={ !! showCount }
						onChange={ ( value ) =>
							setAttributes( { showCount: !! value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<ServerSideRender
					block="wp-usefull-blocks/ub-post-tags"
					attributes={ attributes }
				/>
			</div>
		</Fragment>
	);
}
