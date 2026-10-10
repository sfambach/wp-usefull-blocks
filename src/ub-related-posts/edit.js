/**
 * Editor UI for the UB Related Posts block.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
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
		taxonomy = 'category',
		term = '',
		includeChildren = true,
		excludeCurrent = true,
		count = 10,
		orderBy = 'date',
		showImage = false,
		showDate = false,
		showExcerpt = false,
		columns = 3,
	} = attributes;

	const terms = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, {
				per_page: 100,
				orderby: 'name',
				_fields: 'id,name,slug',
			} ),
		[ taxonomy ]
	);

	const termOptions = [
		{
			value: '',
			label: __( '— Terms of this post —', 'wp-usefull-blocks' ),
		},
		...( terms || [] ).map( ( item ) => ( {
			value: item.slug,
			label: item.name,
		} ) ),
	];

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Selection', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Taxonomy', 'wp-usefull-blocks' ) }
						value={ taxonomy }
						options={ [
							{
								value: 'category',
								label: __( 'Category', 'wp-usefull-blocks' ),
							},
							{
								value: 'post_tag',
								label: __( 'Tag', 'wp-usefull-blocks' ),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { taxonomy: value, term: '' } )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Term', 'wp-usefull-blocks' ) }
						value={ term }
						options={ termOptions }
						onChange={ ( value ) =>
							setAttributes( { term: value } )
						}
					/>
					{ 'category' === taxonomy && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Include subcategories',
								'wp-usefull-blocks'
							) }
							checked={ !! includeChildren }
							onChange={ ( value ) =>
								setAttributes( { includeChildren: !! value } )
							}
						/>
					) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Exclude this post', 'wp-usefull-blocks' ) }
						checked={ !! excludeCurrent }
						onChange={ ( value ) =>
							setAttributes( { excludeCurrent: !! value } )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Number of posts', 'wp-usefull-blocks' ) }
						value={ count }
						onChange={ ( value ) =>
							setAttributes( { count: value || 10 } )
						}
						min={ 1 }
						max={ 50 }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Order by', 'wp-usefull-blocks' ) }
						value={ orderBy }
						options={ [
							{
								value: 'date',
								label: __(
									'Newest first',
									'wp-usefull-blocks'
								),
							},
							{
								value: 'modified',
								label: __(
									'Recently updated',
									'wp-usefull-blocks'
								),
							},
							{
								value: 'title',
								label: __( 'Title', 'wp-usefull-blocks' ),
							},
							{
								value: 'rand',
								label: __( 'Random', 'wp-usefull-blocks' ),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderBy: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Display', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show preview image',
							'wp-usefull-blocks'
						) }
						checked={ !! showImage }
						onChange={ ( value ) =>
							setAttributes( { showImage: !! value } )
						}
					/>
					{ showImage && (
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Columns', 'wp-usefull-blocks' ) }
							value={ columns }
							onChange={ ( value ) =>
								setAttributes( { columns: value || 3 } )
							}
							min={ 1 }
							max={ 4 }
						/>
					) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show date', 'wp-usefull-blocks' ) }
						checked={ !! showDate }
						onChange={ ( value ) =>
							setAttributes( { showDate: !! value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show excerpt', 'wp-usefull-blocks' ) }
						checked={ !! showExcerpt }
						onChange={ ( value ) =>
							setAttributes( { showExcerpt: !! value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<ServerSideRender
					block="wp-usefull-blocks/ub-related-posts"
					attributes={ attributes }
				/>
			</div>
		</Fragment>
	);
}
