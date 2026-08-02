/**
 * Front-end interactivity for the UB Gallery block.
 */

import { getContext, store } from '@wordpress/interactivity';

/**
 * Sync derived focus fields from the selected gallery image.
 *
 * @param {Object} context Interactivity context.
 */
function syncFocusFromSelection( context ) {
	const images = context.images || [];
	const index = Number( context.selectedIndex ) || 0;
	const image = images[ index ] || images[ 0 ] || {};

	context.focusUrl = image.url || '';
	context.focusSrcset = image.srcset || '';
	context.focusSizes = image.sizes || '';
	context.focusAlt = image.alt || '';
	context.focusFullUrl = image.fullUrl || image.url || '';
	context.focusCaption = image.caption || '';
	context.hasFocusCaption = Boolean( image.caption );

	if ( 'attachment' === context.linkTo ) {
		context.focusHref =
			image.attachmentUrl || image.fullUrl || image.url || '';
	} else {
		context.focusHref = image.fullUrl || image.url || '';
	}
}

store( 'wp-usefull-blocks/ub-gallery', {
	state: {
		get isThumbSelected() {
			const context = getContext();
			return (
				Number( context.selectedIndex ) === Number( context.thumbIndex )
			);
		},
	},
	actions: {
		selectImage() {
			const context = getContext();
			const thumbIndex = Number( context.thumbIndex );

			if ( Number.isNaN( thumbIndex ) ) {
				return;
			}

			context.selectedIndex = thumbIndex;
			syncFocusFromSelection( context );
		},
		openLightbox() {
			const context = getContext();

			if ( 'lightbox' !== context.linkTo ) {
				return;
			}

			context.lightboxOpen = true;
		},
		closeLightbox() {
			getContext().lightboxOpen = false;
		},
		stopPropagation( event ) {
			event.stopPropagation();
		},
		handleKeydown( event ) {
			if ( 'Escape' !== event.key ) {
				return;
			}

			const context = getContext();

			if ( context.lightboxOpen ) {
				context.lightboxOpen = false;
			}
		},
	},
} );
