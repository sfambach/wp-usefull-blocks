/**
 * Editor UI for the UB Timeline block.
 *
 * Table editor for entries + Inspector orientation + ServerSideRender preview.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';
import {
	addItem,
	isValidOrientation,
	moveItem,
	normalizeItems,
	removeItem,
	updateItemField,
} from './items';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		orientation = 'vertical',
		items = [],
		initiallyOpen = false,
	} = attributes;

	const entries = normalizeItems( items );
	const safeOrientation = isValidOrientation( orientation )
		? orientation
		: 'vertical';

	const blockProps = useBlockProps( {
		className: 'ub-timeline-editor',
	} );

	const setItems = ( next ) => {
		setAttributes( { items: next } );
	};

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
						label={ __( 'Orientation', 'wp-usefull-blocks' ) }
						value={ safeOrientation }
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
						onChange={ ( value ) => {
							if ( isValidOrientation( value ) ) {
								setAttributes( { orientation: value } );
							}
						} }
						help={ __(
							'On small screens a horizontal timeline stacks vertically.',
							'wp-usefull-blocks'
						) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Expand descriptions by default',
							'wp-usefull-blocks'
						) }
						checked={ !! initiallyOpen }
						onChange={ ( value ) =>
							setAttributes( { initiallyOpen: !! value } )
						}
						help={ __(
							'When off, visitors click a title to reveal its description.',
							'wp-usefull-blocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="ub-timeline-editor__table-wrap">
					<table className="ub-timeline-editor__table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Title', 'wp-usefull-blocks' ) }
								</th>
								<th scope="col">
									{ __( 'Description', 'wp-usefull-blocks' ) }
								</th>
								<th
									scope="col"
									className="ub-timeline-editor__actions-heading"
								>
									<span className="screen-reader-text">
										{ __(
											'Row actions',
											'wp-usefull-blocks'
										) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ entries.length === 0 && (
								<tr>
									<td colSpan={ 3 }>
										<p className="ub-timeline-editor__empty">
											{ __(
												'No entries yet. Add a title and description to build the timeline.',
												'wp-usefull-blocks'
											) }
										</p>
									</td>
								</tr>
							) }
							{ entries.map( ( entry, index ) => (
								<tr key={ entry.id }>
									<td>
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											label={ __(
												'Title',
												'wp-usefull-blocks'
											) }
											hideLabelFromVision
											value={ entry.title }
											onChange={ ( value ) =>
												setItems(
													updateItemField(
														entries,
														index,
														'title',
														value
													)
												)
											}
											placeholder={ __(
												'Entry title…',
												'wp-usefull-blocks'
											) }
										/>
									</td>
									<td>
										<TextareaControl
											__nextHasNoMarginBottom
											label={ __(
												'Description',
												'wp-usefull-blocks'
											) }
											hideLabelFromVision
											value={ entry.description }
											onChange={ ( value ) =>
												setItems(
													updateItemField(
														entries,
														index,
														'description',
														value
													)
												)
											}
											placeholder={ __(
												'Description…',
												'wp-usefull-blocks'
											) }
											rows={ 3 }
										/>
									</td>
									<td className="ub-timeline-editor__actions">
										<Button
											size="small"
											variant="tertiary"
											disabled={ 0 === index }
											onClick={ () =>
												setItems(
													moveItem(
														entries,
														index,
														-1
													)
												)
											}
											label={ __(
												'Move up',
												'wp-usefull-blocks'
											) }
											icon="arrow-up-alt2"
										/>
										<Button
											size="small"
											variant="tertiary"
											disabled={
												index === entries.length - 1
											}
											onClick={ () =>
												setItems(
													moveItem(
														entries,
														index,
														1
													)
												)
											}
											label={ __(
												'Move down',
												'wp-usefull-blocks'
											) }
											icon="arrow-down-alt2"
										/>
										<Button
											size="small"
											variant="tertiary"
											isDestructive
											onClick={ () =>
												setItems(
													removeItem( entries, index )
												)
											}
											label={ __(
												'Remove entry',
												'wp-usefull-blocks'
											) }
											icon="trash"
										/>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
					<Button
						variant="secondary"
						onClick={ () => setItems( addItem( entries ) ) }
						className="ub-timeline-editor__add"
					>
						{ __( 'Add entry', 'wp-usefull-blocks' ) }
					</Button>
				</div>

				<div className="ub-timeline-editor__preview">
					<div className="ub-timeline-editor__preview-label">
						{ __( 'Preview', 'wp-usefull-blocks' ) }
					</div>
					{ entries.length > 0 ? (
						<ServerSideRender
							block="wp-usefull-blocks/ub-timeline"
							attributes={ {
								...attributes,
								orientation: safeOrientation,
								items: entries,
							} }
						/>
					) : (
						<p className="ub-timeline-editor__preview-empty">
							{ __(
								'Add at least one entry to see the front-end preview.',
								'wp-usefull-blocks'
							) }
						</p>
					) }
				</div>
			</div>
		</Fragment>
	);
}
