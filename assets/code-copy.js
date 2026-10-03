( function () {
	'use strict';

	const i18n = window.wpUsefullBlocksCodeCopy || {
		copy: 'Copy',
		copied: 'Copied',
		label: 'Copy code to clipboard',
	};

	function writeClipboard( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}

		return new Promise( function ( resolve, reject ) {
			const ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.setAttribute( 'readonly', '' );
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild( ta );
			ta.select();
			const ok = document.execCommand( 'copy' );
			ta.remove();
			if ( ok ) {
				resolve();
			} else {
				reject();
			}
		} );
	}

	function enhance( pre ) {
		if ( pre.parentNode.classList.contains( 'ub-code-copy' ) ) {
			return;
		}

		const wrap = document.createElement( 'div' );
		wrap.className = 'ub-code-copy';
		pre.parentNode.insertBefore( wrap, pre );
		wrap.appendChild( pre );

		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'ub-code-copy__button';
		btn.textContent = i18n.copy;
		btn.setAttribute( 'aria-label', i18n.label );
		wrap.appendChild( btn );

		btn.addEventListener( 'click', function () {
			const code = pre.querySelector( 'code' ) || pre;
			writeClipboard( code.textContent.replace( /\n$/, '' ) ).then(
				function () {
					btn.textContent = i18n.copied;
					setTimeout( function () {
						btn.textContent = i18n.copy;
					}, 1500 );
				}
			);
		} );
	}

	function init() {
		document.querySelectorAll( 'pre.wp-block-code' ).forEach( enhance );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
