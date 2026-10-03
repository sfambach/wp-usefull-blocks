/**
 * Front-end interactivity for the UB FAQ block.
 */

import { getContext, store } from '@wordpress/interactivity';

store( 'wp-usefull-blocks/ub-faq', {
	actions: {
		toggle() {
			const context = getContext();
			context.isOpen = ! context.isOpen;
		},
	},
} );
