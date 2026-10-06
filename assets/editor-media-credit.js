/**
 * Media library panel for core/image, core/file and UB File.
 *
 * Edits title and source credit of the selected attachment directly in the
 * media library, so missing data can be filled in from the block.
 * Loaded via enqueue_block_editor_assets (no JSX build step).
 *
 * @param {Object} wp WordPress global.
 */
( function ( wp ) {
	if (
		! wp ||
		! wp.hooks ||
		! wp.compose ||
		! wp.blockEditor ||
		! wp.components ||
		! wp.data ||
		! wp.i18n ||
		! wp.element
	) {
		return;
	}

	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, Button } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment, useState } = wp.element;

	const ID_ATTRIBUTES = {
		'core/image': 'id',
		'core/file': 'id',
		'wp-usefull-blocks/ub-file': 'attachmentId',
	};

	const META_AUTHOR = '_ub_credit_author';
	const META_URL = '_ub_credit_url';
	const META_LICENSE = '_ub_credit_license';

	function MediaCreditPanel( { attachmentId } ) {
		const [ saving, setSaving ] = useState( false );

		const { record, hasEdits } = useSelect(
			( select ) => {
				const core = select( 'core' );
				return {
					record: core.getEditedEntityRecord(
						'postType',
						'attachment',
						attachmentId
					),
					hasEdits: core.hasEditsForEntityRecord(
						'postType',
						'attachment',
						attachmentId
					),
				};
			},
			[ attachmentId ]
		);

		const { editEntityRecord, saveEditedEntityRecord } =
			useDispatch( 'core' );
		const { createSuccessNotice, createErrorNotice } =
			useDispatch( 'core/notices' );

		if ( ! record || ! record.id ) {
			return null;
		}

		const meta = record.meta || {};
		const title =
			'string' === typeof record.title
				? record.title
				: ( record.title && record.title.raw ) || '';

		const editMeta = ( key, value ) =>
			editEntityRecord( 'postType', 'attachment', attachmentId, {
				meta: { ...meta, [ key ]: value },
			} );

		const save = () => {
			setSaving( true );
			saveEditedEntityRecord( 'postType', 'attachment', attachmentId )
				.then( () =>
					createSuccessNotice(
						__( 'Saved to media library.', 'wp-usefull-blocks' ),
						{ type: 'snackbar' }
					)
				)
				.catch( () =>
					createErrorNotice(
						__(
							'Could not save to media library.',
							'wp-usefull-blocks'
						),
						{ type: 'snackbar' }
					)
				)
				.finally( () => setSaving( false ) );
		};

		return el(
			PanelBody,
			{
				title: __( 'Media library & source', 'wp-usefull-blocks' ),
				initialOpen: true,
			},
			el( TextControl, {
				label: __( 'Title', 'wp-usefull-blocks' ),
				value: title,
				onChange: ( value ) =>
					editEntityRecord( 'postType', 'attachment', attachmentId, {
						title: value,
					} ),
				__nextHasNoMarginBottom: true,
			} ),
			el( TextControl, {
				label: __( 'Source / author', 'wp-usefull-blocks' ),
				value: meta[ META_AUTHOR ] || '',
				onChange: ( value ) => editMeta( META_AUTHOR, value ),
				__nextHasNoMarginBottom: true,
			} ),
			el( TextControl, {
				label: __( 'Source URL', 'wp-usefull-blocks' ),
				type: 'url',
				value: meta[ META_URL ] || '',
				onChange: ( value ) => editMeta( META_URL, value ),
				__nextHasNoMarginBottom: true,
			} ),
			el( TextControl, {
				label: __( 'License', 'wp-usefull-blocks' ),
				help: __(
					'Stored in the media library and shown wherever this file is used.',
					'wp-usefull-blocks'
				),
				value: meta[ META_LICENSE ] || '',
				onChange: ( value ) => editMeta( META_LICENSE, value ),
				__nextHasNoMarginBottom: true,
			} ),
			el(
				Button,
				{
					variant: 'secondary',
					onClick: save,
					disabled: ! hasEdits || saving,
					isBusy: saving,
				},
				__( 'Save to media library', 'wp-usefull-blocks' )
			)
		);
	}

	const withMediaCreditPanel = createHigherOrderComponent(
		( BlockEdit ) => ( props ) => {
			const attribute = ID_ATTRIBUTES[ props.name ];
			const attachmentId = attribute
				? Number( props.attributes[ attribute ] ) || 0
				: 0;

			if ( ! attachmentId || ! props.isSelected ) {
				return el( BlockEdit, props );
			}

			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					InspectorControls,
					null,
					el( MediaCreditPanel, { attachmentId } )
				)
			);
		},
		'withMediaCreditPanel'
	);

	addFilter(
		'editor.BlockEdit',
		'wp-usefull-blocks/media-credit',
		withMediaCreditPanel
	);
} )( window.wp );
