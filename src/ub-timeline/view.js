/**
 * Front-end interactivity for the UB Timeline block.
 */

import { getContext, store } from '@wordpress/interactivity';

store( 'wp-usefull-blocks/ub-timeline', {
	actions: {
		toggle() {
			const context = getContext();
			context.isOpen = ! context.isOpen;
		},
	},
} );
