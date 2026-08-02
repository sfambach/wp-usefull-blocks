/**
 * Editor UI for the UB Gallery block.
 *
 * Inspector + toolbar controls mirror core/gallery where they apply to this layout.
 */

import { __, _x } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	MediaPlaceholder,
	MediaUpload,
	MediaUploadCheck,
	RichText,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	MenuGroup,
	MenuItem,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	ToolbarButton,
	ToolbarDropdownMenu,
	ToolbarGroup,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import {
	fullscreen,
	image as imageIcon,
	link as linkIcon,
	linkOff,
	media as mediaIcon,
} from '@wordpress/icons';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

const LINK_DESTINATION_NONE = 'none';
const LINK_DESTINATION_MEDIA = 'media';
const LINK_DESTINATION_ATTACHMENT = 'attachment';
const LINK_DESTINATION_LIGHTBOX = 'lightbox';
const MAX_COLUMNS = 8;

const LINK_OPTIONS = [
	{
		icon: imageIcon,
		label: __( 'Link images to attachment pages', 'wp-usefull-blocks' ),
		value: LINK_DESTINATION_ATTACHMENT,
		info: __(
			'Link images to their attachment pages.',
			'wp-usefull-blocks'
		),
	},
	{
		icon: mediaIcon,
		label: __( 'Link images to media files', 'wp-usefull-blocks' ),
		value: LINK_DESTINATION_MEDIA,
		info: __( 'Link images directly to media files.', 'wp-usefull-blocks' ),
	},
	{
		icon: fullscreen,
		label: __( 'Enlarge on click', 'wp-usefull-blocks' ),
		value: LINK_DESTINATION_LIGHTBOX,
		info: __( 'Scale images with a lightbox effect.', 'wp-usefull-blocks' ),
	},
	{
		icon: linkOff,
		label: _x( 'None', 'Media item link option', 'wp-usefull-blocks' ),
		value: LINK_DESTINATION_NONE,
		info: __( 'Do not link the focus image.', 'wp-usefull-blocks' ),
	},
];

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
 * Default column count like core/gallery.
 *
 * @param {number} imageCount Number of images.
 * @return {number} Default columns.
 */
function defaultColumnsNumber( imageCount ) {
	return imageCount ? Math.min( 3, imageCount ) : 3;
}

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		images = [],
		linkTo = LINK_DESTINATION_LIGHTBOX,
		linkTarget = '',
		sizeSlug = 'large',
		imageCrop = true,
		randomOrder = false,
		columns,
		caption = '',
	} = attributes;

	const hasImages = images.length > 0;
	const imageIds = images.map( ( image ) => image.id ).filter( Boolean );
	const hasLinkTo = linkTo && LINK_DESTINATION_NONE !== linkTo;
	const columnCount = columns || defaultColumnsNumber( images.length );

	const imageSizeOptions = useSelect( ( select ) => {
		const settings = select( blockEditorStore ).getSettings();
		const sizes = settings?.imageSizes || [];

		return sizes.map( ( size ) => ( {
			value: size.slug,
			label: size.name,
		} ) );
	}, [] );

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
			columns: columns
				? Math.min( columns, nextImages.length )
				: undefined,
		} );
	};

	const onRemoveImages = () => {
		setAttributes( {
			images: [],
			selectedIndex: 0,
			columns: undefined,
			caption: '',
		} );
	};

	const setLinkTo = ( value ) => {
		const nextAttributes = {
			linkTo: value,
			onImageClick: value,
		};

		if (
			LINK_DESTINATION_NONE === value ||
			LINK_DESTINATION_LIGHTBOX === value
		) {
			nextAttributes.linkTarget = '';
		}

		setAttributes( nextAttributes );
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
			<BlockControls group="block">
				<ToolbarDropdownMenu
					icon={ linkIcon }
					label={ __( 'Link', 'wp-usefull-blocks' ) }
				>
					{ ( { onClose } ) => (
						<MenuGroup>
							{ LINK_OPTIONS.map( ( linkItem ) => {
								const isOptionSelected =
									linkTo === linkItem.value;

								return (
									<MenuItem
										key={ linkItem.value }
										isSelected={ isOptionSelected }
										iconPosition="left"
										icon={ linkItem.icon }
										role="menuitemradio"
										info={ linkItem.info }
										onClick={ () => {
											setLinkTo( linkItem.value );
											onClose();
										} }
									>
										{ linkItem.label }
									</MenuItem>
								);
							} ) }
						</MenuGroup>
					) }
				</ToolbarDropdownMenu>
			</BlockControls>

			<BlockControls group="other">
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
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImages }
							allowedTypes={ [ 'image' ] }
							multiple
							gallery
							addToGallery
							value={ imageIds }
							render={ ( { open } ) => (
								<ToolbarButton
									onClick={ open }
									icon="plus"
									label={ __( 'Add', 'wp-usefull-blocks' ) }
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
				<PanelBody title={ __( 'Settings', 'wp-usefull-blocks' ) }>
					{ images.length > 1 && (
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Columns', 'wp-usefull-blocks' ) }
							help={ __(
								'Number of thumbnail columns under the focus image.',
								'wp-usefull-blocks'
							) }
							value={ columnCount }
							onChange={ ( value ) =>
								setAttributes( { columns: value } )
							}
							min={ 1 }
							max={ Math.min( MAX_COLUMNS, images.length ) }
							required
						/>
					) }
					{ imageSizeOptions.length > 0 && (
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Resolution', 'wp-usefull-blocks' ) }
							help={ __(
								'Select the size of the source images.',
								'wp-usefull-blocks'
							) }
							value={ sizeSlug }
							options={ imageSizeOptions }
							onChange={ ( value ) =>
								setAttributes( { sizeSlug: value } )
							}
						/>
					) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Crop images to fit',
							'wp-usefull-blocks'
						) }
						checked={ !! imageCrop }
						onChange={ ( value ) =>
							setAttributes( { imageCrop: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Randomize order', 'wp-usefull-blocks' ) }
						checked={ !! randomOrder }
						onChange={ ( value ) =>
							setAttributes( { randomOrder: value } )
						}
					/>
					{ hasLinkTo && LINK_DESTINATION_LIGHTBOX !== linkTo && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Open images in new tab',
								'wp-usefull-blocks'
							) }
							checked={ '_blank' === linkTarget }
							onChange={ ( value ) =>
								setAttributes( {
									linkTarget: value ? '_blank' : '',
								} )
							}
						/>
					) }
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
				<RichText
					tagName="figcaption"
					className="ub-gallery-editor__caption"
					placeholder={ __(
						'Write gallery caption…',
						'wp-usefull-blocks'
					) }
					value={ caption }
					onChange={ ( value ) =>
						setAttributes( { caption: value } )
					}
				/>
			</div>
		</Fragment>
	);
}
