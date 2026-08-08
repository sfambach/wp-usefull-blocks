/**
 * Heading level − / + toolbar buttons for core/heading.
 *
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
		! wp.i18n ||
		! wp.element
	) {
		return;
	}

	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { BlockControls } = wp.blockEditor;
	const { ToolbarGroup, ToolbarButton } = wp.components;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment } = wp.element;

	const withHeadingLevelButtons = createHigherOrderComponent(
		( BlockEdit ) => {
			return ( props ) => {
				if ( 'core/heading' !== props.name ) {
					return el( BlockEdit, props );
				}

				const level = Number( props.attributes.level ) || 2;
				const setLevel = ( next ) => {
					const clamped = Math.min( 6, Math.max( 1, next ) );
					props.setAttributes( { level: clamped } );
				};

				return el(
					Fragment,
					null,
					el(
						BlockControls,
						{ group: 'block' },
						el(
							ToolbarGroup,
							null,
							el( ToolbarButton, {
								icon: 'minus',
								label: __(
									'Decrease heading level',
									'wp-usefull-blocks'
								),
								onClick: () => setLevel( level - 1 ),
								disabled: level <= 1,
							} ),
							el( ToolbarButton, {
								icon: 'plus',
								label: __(
									'Increase heading level',
									'wp-usefull-blocks'
								),
								onClick: () => setLevel( level + 1 ),
								disabled: level >= 6,
							} )
						)
					),
					el( BlockEdit, props )
				);
			};
		},
		'withHeadingLevelButtons'
	);

	addFilter(
		'editor.BlockEdit',
		'wp-usefull-blocks/heading-level-toolbar',
		withHeadingLevelButtons
	);
} )( window.wp );
