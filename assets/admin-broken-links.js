/**
 * Broken Links admin: in-place ampel update + copy old URL into new URL field.
 */
( function () {
	'use strict';

	function showNotice( type, message ) {
		const wrap = document.querySelector( '.wrap' );
		if ( ! wrap ) {
			return;
		}

		document.querySelectorAll( '.ub-inline-notice' ).forEach( ( el ) => el.remove() );

		const notice = document.createElement( 'div' );
		notice.className = 'notice notice-' + type + ' is-dismissible ub-inline-notice';
		const p = document.createElement( 'p' );
		p.textContent = message;
		notice.appendChild( p );
		wrap.insertBefore( notice, wrap.querySelector( 'h1' )?.nextElementSibling || wrap.firstChild );
	}

	function setBusy( form, busy ) {
		const button = form.querySelector( 'input[type="submit"], button[type="submit"]' );
		if ( button ) {
			button.disabled = !! busy;
		}
		form.classList.toggle( 'ub-is-busy', !! busy );
		const icon = form.querySelector( '.dashicons-update' );
		if ( icon ) {
			icon.classList.toggle( 'spin', !! busy );
		}
	}

	function applyStatusHtml( statusCell, statusHtml ) {
		if ( ! statusCell || ! statusHtml ) {
			return;
		}

		const recheck = statusCell.querySelector( '.ub-broken-links-recheck' );
		statusCell.innerHTML = statusHtml;
		const line = statusCell.querySelector( '.ub-status-line' );
		if ( recheck && line ) {
			line.appendChild( recheck );
		} else if ( recheck ) {
			statusCell.appendChild( recheck );
		}
	}

	function copyOldUrlIntoField( button ) {
		const targetId = button.getAttribute( 'data-ub-target' );
		const oldUrl = button.getAttribute( 'data-ub-old-url' ) || '';
		if ( ! targetId || ! oldUrl ) {
			return;
		}

		const field = document.getElementById( targetId );
		if ( ! field ) {
			return;
		}

		field.value = oldUrl;
		field.focus();
		field.select?.();
	}

	async function postForm( form, action ) {
		const ajaxUrl = window.wpUsefullBlocksBrokenLinks?.ajaxUrl;
		if ( ! ajaxUrl || ! action ) {
			return null;
		}

		const data = new FormData( form );
		data.set( 'action', action );

		const response = await fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} );
		return response.json();
	}

	async function submitReplace( form ) {
		const row = form.closest( 'tr' );
		const statusCell = row ? row.querySelector( '.ub-status-summary' ) : null;
		const action = window.wpUsefullBlocksBrokenLinks?.replaceAction;

		const dataPreview = new FormData( form );
		if ( ! dataPreview.get( 'new_url' ) ) {
			showNotice(
				'error',
				window.wpUsefullBlocksBrokenLinks?.i18n?.missingUrl ||
					'Please enter a new URL.'
			);
			return;
		}

		setBusy( form, true );

		try {
			const payload = await postForm( form, action );

			if ( ! payload || ! payload.success ) {
				const msg =
					payload?.data?.message ||
					window.wpUsefullBlocksBrokenLinks?.i18n?.error ||
					'Something went wrong.';
				showNotice( 'error', msg );
				return;
			}

			applyStatusHtml( statusCell, payload.data.statusHtml );

			showNotice(
				payload.data.status === 'ok' ? 'success' : 'warning',
				payload.data.message ||
					window.wpUsefullBlocksBrokenLinks?.i18n?.updated ||
					'Updated.'
			);
		} catch ( err ) {
			showNotice(
				'error',
				window.wpUsefullBlocksBrokenLinks?.i18n?.error || 'Something went wrong.'
			);
		} finally {
			setBusy( form, false );
		}
	}

	async function submitRecheck( form ) {
		const row = form.closest( 'tr' );
		const statusCell = row ? row.querySelector( '.ub-status-summary' ) : null;
		const action = window.wpUsefullBlocksBrokenLinks?.recheckAction;

		setBusy( form, true );

		try {
			const payload = await postForm( form, action );

			if ( ! payload || ! payload.success ) {
				const msg =
					payload?.data?.message ||
					window.wpUsefullBlocksBrokenLinks?.i18n?.error ||
					'Something went wrong.';
				showNotice( 'error', msg );
				return;
			}

			applyStatusHtml( statusCell, payload.data.statusHtml );

			showNotice(
				payload.data.status === 'ok' ? 'success' : 'warning',
				payload.data.message ||
					window.wpUsefullBlocksBrokenLinks?.i18n?.rechecked ||
					'Rechecked.'
			);
		} catch ( err ) {
			showNotice(
				'error',
				window.wpUsefullBlocksBrokenLinks?.i18n?.error || 'Something went wrong.'
			);
		} finally {
			setBusy( form, false );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		const button = event.target.closest( '.ub-copy-old-url' );
		if ( ! button ) {
			return;
		}
		event.preventDefault();
		copyOldUrlIntoField( button );
	} );

	document.addEventListener( 'submit', function ( event ) {
		const form = event.target;
		if ( ! ( form instanceof HTMLFormElement ) ) {
			return;
		}

		if ( form.classList.contains( 'ub-broken-links-replace' ) ) {
			event.preventDefault();
			submitReplace( form );
			return;
		}

		if ( form.classList.contains( 'ub-broken-links-recheck' ) ) {
			event.preventDefault();
			submitRecheck( form );
		}
	} );
}() );
