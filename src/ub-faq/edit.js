/**
 * Editor UI for the UB FAQ block.
 *
 * Table editor for Q&A entries + ServerSideRender preview.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';
import {
	addItem,
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
	const { items = [], initiallyOpen = false } = attributes;

	const entries = normalizeItems( items );

	const blockProps = useBlockProps( {
		className: 'ub-faq-editor',
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
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Expand answers by default',
							'wp-usefull-blocks'
						) }
						checked={ !! initiallyOpen }
						onChange={ ( value ) =>
							setAttributes( { initiallyOpen: !! value } )
						}
						help={ __(
							'When off, visitors click a question to reveal its answer.',
							'wp-usefull-blocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="ub-faq-editor__table-wrap">
					<table className="ub-faq-editor__table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Question', 'wp-usefull-blocks' ) }
								</th>
								<th scope="col">
									{ __( 'Answer', 'wp-usefull-blocks' ) }
								</th>
								<th
									scope="col"
									className="ub-faq-editor__actions-heading"
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
										<p className="ub-faq-editor__empty">
											{ __(
												'No questions yet. Add a question and answer to build the FAQ.',
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
												'Question',
												'wp-usefull-blocks'
											) }
											hideLabelFromVision
											value={ entry.question }
											onChange={ ( value ) =>
												setItems(
													updateItemField(
														entries,
														index,
														'question',
														value
													)
												)
											}
											placeholder={ __(
												'Question…',
												'wp-usefull-blocks'
											) }
										/>
									</td>
									<td>
										<TextareaControl
											__nextHasNoMarginBottom
											label={ __(
												'Answer',
												'wp-usefull-blocks'
											) }
											hideLabelFromVision
											value={ entry.answer }
											onChange={ ( value ) =>
												setItems(
													updateItemField(
														entries,
														index,
														'answer',
														value
													)
												)
											}
											placeholder={ __(
												'Answer…',
												'wp-usefull-blocks'
											) }
											rows={ 3 }
										/>
									</td>
									<td className="ub-faq-editor__actions">
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
						className="ub-faq-editor__add"
					>
						{ __( 'Add question', 'wp-usefull-blocks' ) }
					</Button>
				</div>

				<div className="ub-faq-editor__preview">
					<div className="ub-faq-editor__preview-label">
						{ __( 'Preview', 'wp-usefull-blocks' ) }
					</div>
					{ entries.length > 0 ? (
						<ServerSideRender
							block="wp-usefull-blocks/ub-faq"
							attributes={ {
								...attributes,
								items: entries,
							} }
						/>
					) : (
						<p className="ub-faq-editor__preview-empty">
							{ __(
								'Add at least one question to see the front-end preview.',
								'wp-usefull-blocks'
							) }
						</p>
					) }
				</div>
			</div>
		</Fragment>
	);
}
