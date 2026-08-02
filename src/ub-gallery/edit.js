/**
 * Editor UI for the UB Gallery block.
 */

import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	MediaPlaceholder,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	SelectControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

/**
 * Map media library items to the block's image attribute shape.
 *
 * @param {Array} media Selected media items.
 * @return {Array} Normalized image objects.
 */
function mapMediaToImages( media ) {
	return ( media || [] ).map( ( item ) => {
		const largeUrl =
			item?.sizes?.large?.url ||
			item?.sizes?.medium_large?.url ||
			item?.url ||
			'';

		return {
			id: item.id,
			url: largeUrl,
			alt: item.alt || '',
			caption: item.caption || '',
			fullUrl: item.url || largeUrl,
		};
	} );
}

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { images = [], onImageClick = 'lightbox' } = attributes;
	const hasImages = images.length > 0;
	const imageIds = images.map( ( image ) => image.id ).filter( Boolean );

	const blockProps = useBlockProps( {
		className: 'ub-gallery-editor',
	} );

	const onSelectImages = ( media ) => {
		const nextImages = mapMediaToImages(
			Array.isArray( media ) ? media : [ media ]
		);

		if ( ! nextImages.length ) {
			return;
		}

		setAttributes( {
			images: nextImages,
			selectedIndex: 0,
		} );
	};

	const onRemoveImages = () => {
		setAttributes( {
			images: [],
			selectedIndex: 0,
		} );
	};

	if ( ! hasImages ) {
		return (
			<div { ...blockProps }>
				<MediaPlaceholder
					icon="format-gallery"
					labels={ {
						title: __( 'UB Gallery', 'wp-usefull-blocks' ),
						instructions: __(
							'Drag images, upload new ones, or select from the media library.',
							'wp-usefull-blocks'
						),
					} }
					onSelect={ onSelectImages }
					accept="image/*"
					allowedTypes={ [ 'image' ] }
					multiple
					gallery
					value={ imageIds }
				/>
			</div>
		);
	}

	return (
		<Fragment>
			<BlockControls>
				<ToolbarGroup>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImages }
							allowedTypes={ [ 'image' ] }
							multiple
							gallery
							value={ imageIds }
							render={ ( { open } ) => (
								<ToolbarButton
									onClick={ open }
									icon="edit"
									label={ __(
										'Edit gallery',
										'wp-usefull-blocks'
									) }
								/>
							) }
						/>
					</MediaUploadCheck>
					<ToolbarButton
						onClick={ onRemoveImages }
						icon="trash"
						label={ __( 'Remove images', 'wp-usefull-blocks' ) }
					/>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={ __( 'Gallery settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<SelectControl
						label={ __(
							'On click of the focus image',
							'wp-usefull-blocks'
						) }
						value={ onImageClick }
						options={ [
							{
								label: __( 'Lightbox', 'wp-usefull-blocks' ),
								value: 'lightbox',
							},
							{
								label: __(
									'Link to media file',
									'wp-usefull-blocks'
								),
								value: 'media',
							},
							{
								label: __( 'None', 'wp-usefull-blocks' ),
								value: 'none',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { onImageClick: value } )
						}
						help={ __(
							'What happens when visitors click the large focus image.',
							'wp-usefull-blocks'
						) }
					/>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImages }
							allowedTypes={ [ 'image' ] }
							multiple
							gallery
							value={ imageIds }
							render={ ( { open } ) => (
								<Button
									variant="secondary"
									onClick={ open }
									style={ { marginTop: '8px' } }
								>
									{ __( 'Edit images', 'wp-usefull-blocks' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="ub-gallery-editor__preview-label">
					{ __( 'Preview', 'wp-usefull-blocks' ) }
				</div>
				<ServerSideRender
					block="wp-usefull-blocks/ub-gallery"
					attributes={ attributes }
				/>
			</div>
		</Fragment>
	);
}
