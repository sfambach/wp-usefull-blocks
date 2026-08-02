/**
 * Front-end interactivity for the UB Gallery block.
 */

import { getContext, store } from '@wordpress/interactivity';

const { state } = store( 'wp-usefull-blocks/ub-gallery', {
	state: {
		get focusImage() {
			const context = getContext();
			const images = context.images || [];
			const index = Number( context.selectedIndex ) || 0;
			return images[ index ] || images[ 0 ] || {};
		},
		get focusAlt() {
			return state.focusImage.alt || '';
		},
		get focusUrl() {
			return state.focusImage.url || '';
		},
		get focusSrcset() {
			return state.focusImage.srcset || undefined;
		},
		get focusSizes() {
			return state.focusImage.sizes || undefined;
		},
		get focusFullUrl() {
			return state.focusImage.fullUrl || state.focusImage.url || '';
		},
		get focusCaption() {
			return state.focusImage.caption || '';
		},
		get hasCaption() {
			return Boolean( state.focusCaption );
		},
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
		},
		openLightbox() {
			const context = getContext();

			if ( 'lightbox' !== context.onImageClick ) {
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
