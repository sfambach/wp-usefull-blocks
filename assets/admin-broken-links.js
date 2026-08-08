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
		const button = form.querySelector( 'button, input[type="submit"]' );
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

	function getRowNewUrl( row ) {
		const field = row ? row.querySelector( 'textarea[name="new_url"]' ) : null;
		return field && field.value ? field.value.trim() : '';
	}

	function setRowRecheckUrl( row, url ) {
		if ( ! row || ! url ) {
			return;
		}
		const input = row.querySelector( '.ub-broken-links-recheck input[name="url"]' );
		if ( input ) {
			input.value = url;
		}
		row.dataset.ubUrl = url;
	}

	async function postData( data ) {
		const ajaxUrl = window.wpUsefullBlocksBrokenLinks?.ajaxUrl;
		if ( ! ajaxUrl ) {
			throw new Error( 'Missing AJAX config' );
		}

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

		const data = new FormData( form );
		data.set( 'action', action );
		const newUrl = ( data.get( 'new_url' ) || '' ).toString().trim();

		if ( ! newUrl ) {
			showNotice(
				'error',
				window.wpUsefullBlocksBrokenLinks?.i18n?.missingUrl ||
					'Please enter a new URL.'
			);
			return;
		}

		setBusy( form, true );

		try {
			const payload = await postData( data );

			if ( ! payload || ! payload.success ) {
				const msg =
					payload?.data?.message ||
					window.wpUsefullBlocksBrokenLinks?.i18n?.error ||
					'Something went wrong.';
				showNotice( 'error', msg );
				return;
			}

			applyStatusHtml( statusCell, payload.data.statusHtml );
			setRowRecheckUrl( row, newUrl );
			if ( row ) {
				row.dataset.ubStatus = payload.data.status || '';
			}

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
		const newUrl = getRowNewUrl( row );
		const fallbackUrl = form.querySelector( 'input[name="url"]' )?.value || '';
		const checkUrl = newUrl || fallbackUrl;
		const indexUrl = row?.dataset?.ubUrl || fallbackUrl || checkUrl;

		if ( ! checkUrl ) {
			showNotice(
				'error',
				window.wpUsefullBlocksBrokenLinks?.i18n?.missingUrl ||
					'Please enter a new URL.'
			);
			return;
		}

		const data = new FormData( form );
		data.set( 'action', action );
		data.set( 'url', checkUrl );
		data.set( 'index_url', indexUrl );

		setBusy( form, true );

		try {
			const payload = await postData( data );

			if ( ! payload || ! payload.success ) {
				const msg =
					payload?.data?.message ||
					window.wpUsefullBlocksBrokenLinks?.i18n?.error ||
					'Something went wrong.';
				showNotice( 'error', msg );
				return;
			}

			applyStatusHtml( statusCell, payload.data.statusHtml );
			if ( newUrl ) {
				setRowRecheckUrl( row, newUrl );
			}
			if ( row ) {
				row.dataset.ubStatus = payload.data.status || '';
			}

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
		const copyBtn = event.target.closest( '.ub-copy-old-url' );
		if ( copyBtn ) {
			event.preventDefault();
			copyOldUrlIntoField( copyBtn );
			return;
		}

		const recheckBtn = event.target.closest( '.ub-recheck-button' );
		if ( recheckBtn ) {
			event.preventDefault();
			event.stopPropagation();
			const form = recheckBtn.closest( 'form.ub-broken-links-recheck' );
			if ( form ) {
				submitRecheck( form );
			}
		}
	} );

	document.addEventListener( 'submit', function ( event ) {
		const form = event.target;
		if ( ! ( form instanceof HTMLFormElement ) ) {
			return;
		}

		// Never allow classic navigation for these forms.
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
