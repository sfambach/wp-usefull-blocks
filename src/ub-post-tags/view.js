/**
 * Front end for the UB Post Tags block: a tag click toggles its post list.
 */

document.addEventListener( 'click', ( event ) => {
	const tag = event.target.closest( '.ub-post-tags__tag[data-ub-panel]' );

	if ( ! tag ) {
		return;
	}

	event.preventDefault();

	const root = tag.closest( '.ub-post-tags' );
	const open = 'true' !== tag.getAttribute( 'aria-expanded' );

	root.querySelectorAll( '.ub-post-tags__tag[data-ub-panel]' ).forEach(
		( other ) => {
			const panel = root.querySelector(
				'#' + other.getAttribute( 'data-ub-panel' )
			);
			const active = open && other === tag;

			other.setAttribute( 'aria-expanded', active ? 'true' : 'false' );
			if ( panel ) {
				panel.hidden = ! active;
			}
		}
	);
} );
