/**
 * Editor UI for the UB Timeline block.
 *
 * Table editor for title/description rows + ServerSideRender preview.
 */

import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	Placeholder,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { backup } from '@wordpress/icons';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

/**
 * Create a stable unique id for a new timeline row.
 *
 * @return {string} Row id.
 */
function createItemId() {
	if (
		'undefined' !== typeof crypto &&
		'function' === typeof crypto.randomUUID
	) {
		return crypto.randomUUID();
	}

	return `item-${ Date.now() }-${ Math.floor( Math.random() * 100000 ) }`;
}

/**
 * Normalize items to a safe array of objects.
 *
 * @param {Array} items Raw items attribute.
 * @return {Array} Normalized items.
 */
function normalizeItems( items ) {
	return Array.isArray( items ) ? items : [];
}

/**
 * UB Timeline editor component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute updater.
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { orientation = 'vertical', initiallyOpen = false } = attributes;
	const items = normalizeItems( attributes.items );
	const blockProps = useBlockProps( {
		className: 'ub-timeline-editor',
	} );

	const updateItem = ( index, key, value ) => {
		const next = items.map( ( item, itemIndex ) =>
			itemIndex === index
				? {
						...item,
						[ key ]: value,
				  }
				: item
		);
		setAttributes( { items: next } );
	};

	const addItem = () => {
		setAttributes( {
			items: [
				...items,
				{
					id: createItemId(),
					title: '',
					description: '',
				},
			],
		} );
	};

	const removeItem = ( index ) => {
		setAttributes( {
			items: items.filter( ( _item, itemIndex ) => itemIndex !== index ),
		} );
	};

	const moveItem = ( index, direction ) => {
		const target = index + direction;

		if ( target < 0 || target >= items.length ) {
			return;
		}

		const next = [ ...items ];
		const [ moved ] = next.splice( index, 1 );
		next.splice( target, 0, moved );
		setAttributes( { items: next } );
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Timeline settings', 'wp-usefull-blocks' ) }
					initialOpen
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Orientation', 'wp-usefull-blocks' ) }
						value={ orientation }
						options={ [
							{
								label: __( 'Vertical', 'wp-usefull-blocks' ),
								value: 'vertical',
							},
							{
								label: __( 'Horizontal', 'wp-usefull-blocks' ),
								value: 'horizontal',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								orientation:
									'horizontal' === value
										? 'horizontal'
										: 'vertical',
							} )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Start with descriptions open',
							'wp-usefull-blocks'
						) }
						checked={ !! initiallyOpen }
						onChange={ ( value ) =>
							setAttributes( { initiallyOpen: value } )
						}
						help={ __(
							'When off, visitors expand each entry by clicking its title.',
							'wp-usefull-blocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			{ 0 === items.length ? (
				<Placeholder
					icon={ backup }
					label={ __( 'UB Timeline', 'wp-usefull-blocks' ) }
					instructions={ __(
						'Add timeline entries with a title and description. Visitors can expand each description on the front end.',
						'wp-usefull-blocks'
					) }
				>
					<Button variant="primary" onClick={ addItem }>
						{ __( 'Add entry', 'wp-usefull-blocks' ) }
					</Button>
				</Placeholder>
			) : (
				<>
					<div className="ub-timeline-editor__table" role="table">
						<div
							className="ub-timeline-editor__row ub-timeline-editor__row--head"
							role="row"
						>
							<div
								className="ub-timeline-editor__cell"
								role="columnheader"
							>
								{ __( 'Title', 'wp-usefull-blocks' ) }
							</div>
							<div
								className="ub-timeline-editor__cell"
								role="columnheader"
							>
								{ __( 'Description', 'wp-usefull-blocks' ) }
							</div>
							<div
								className="ub-timeline-editor__cell ub-timeline-editor__cell--actions"
								role="columnheader"
							>
								<span className="screen-reader-text">
									{ __( 'Actions', 'wp-usefull-blocks' ) }
								</span>
							</div>
						</div>
						{ items.map( ( item, index ) => (
							<div
								key={ item.id || `row-${ index }` }
								className="ub-timeline-editor__row"
								role="row"
							>
								<div
									className="ub-timeline-editor__cell"
									role="cell"
								>
									<TextControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										label={ sprintf(
											/* translators: %d: entry number */
											__(
												'Title for entry %d',
												'wp-usefull-blocks'
											),
											index + 1
										) }
										hideLabelFromVision
										value={ item.title || '' }
										onChange={ ( value ) =>
											updateItem( index, 'title', value )
										}
										placeholder={ __(
											'Entry title',
											'wp-usefull-blocks'
										) }
									/>
								</div>
								<div
									className="ub-timeline-editor__cell"
									role="cell"
								>
									<TextareaControl
										__nextHasNoMarginBottom
										label={ sprintf(
											/* translators: %d: entry number */
											__(
												'Description for entry %d',
												'wp-usefull-blocks'
											),
											index + 1
										) }
										hideLabelFromVision
										value={ item.description || '' }
										onChange={ ( value ) =>
											updateItem(
												index,
												'description',
												value
											)
										}
										placeholder={ __(
											'Entry description',
											'wp-usefull-blocks'
										) }
										rows={ 3 }
									/>
								</div>
								<div
									className="ub-timeline-editor__cell ub-timeline-editor__cell--actions"
									role="cell"
								>
									<Button
										variant="tertiary"
										size="small"
										onClick={ () => moveItem( index, -1 ) }
										disabled={ 0 === index }
										aria-label={ sprintf(
											/* translators: %d: entry number */
											__(
												'Move entry %d up',
												'wp-usefull-blocks'
											),
											index + 1
										) }
									>
										{ __( 'Up', 'wp-usefull-blocks' ) }
									</Button>
									<Button
										variant="tertiary"
										size="small"
										onClick={ () => moveItem( index, 1 ) }
										disabled={ index === items.length - 1 }
										aria-label={ sprintf(
											/* translators: %d: entry number */
											__(
												'Move entry %d down',
												'wp-usefull-blocks'
											),
											index + 1
										) }
									>
										{ __( 'Down', 'wp-usefull-blocks' ) }
									</Button>
									<Button
										variant="tertiary"
										isDestructive
										size="small"
										onClick={ () => removeItem( index ) }
										aria-label={ sprintf(
											/* translators: %d: entry number */
											__(
												'Remove entry %d',
												'wp-usefull-blocks'
											),
											index + 1
										) }
									>
										{ __( 'Remove', 'wp-usefull-blocks' ) }
									</Button>
								</div>
							</div>
						) ) }
					</div>

					<div className="ub-timeline-editor__toolbar">
						<Button variant="secondary" onClick={ addItem }>
							{ __( 'Add entry', 'wp-usefull-blocks' ) }
						</Button>
					</div>

					<div className="ub-timeline-editor__preview">
						<div className="ub-timeline-editor__preview-label">
							{ __( 'Preview', 'wp-usefull-blocks' ) }
						</div>
						<ServerSideRender
							block="wp-usefull-blocks/ub-timeline"
							attributes={ attributes }
						/>
					</div>
				</>
			) }
		</div>
	);
}
