/**
 * Auto / manual URL status checks against the plugin REST route.
 *
 * Checks are serialized globally so multiple UB Link / UB File blocks
 * do not overwhelm the local PHP server (wp-now returns 502 under parallel
 * outbound HTTP).
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/** @type {Promise<unknown>} */
let checkQueue = Promise.resolve();

/**
 * Run tasks one after another across all hook instances.
 *
 * @param {() => Promise<unknown>} task Task.
 * @return {Promise<unknown>} Result.
 */
function enqueueCheck( task ) {
	const run = checkQueue.then( task, task );
	// Keep the queue alive even if a task fails.
	checkQueue = run.then(
		() => undefined,
		() => undefined
	);
	return run;
}

/**
 * @param {Object}   args
 * @param {string}   args.url
 * @param {boolean}  args.enabled
 * @param {string}   args.initialStatus
 * @param {number}   [args.lastChecked]
 * @param {Function} args.onStatus
 * @return {{checking:boolean,error:string,checkNow:Function}} Status helpers.
 */
export default function useUrlStatus( {
	url,
	enabled,
	initialStatus = 'unknown',
	lastChecked = 0,
	onStatus,
} ) {
	const [ checking, setChecking ] = useState( false );
	const [ error, setError ] = useState( '' );
	const lastCheckedUrl = useRef( '' );
	const onStatusRef = useRef( onStatus );
	onStatusRef.current = onStatus;

	const checkNow = async ( targetUrl = url, options = {} ) => {
		const silent = !! options.silent;
		if ( ! silent ) {
			setError( '' );
		}

		if ( ! targetUrl ) {
			return null;
		}

		setChecking( true );

		try {
			const result = await enqueueCheck( () =>
				apiFetch( {
					path: '/wp-usefull-blocks/v1/check-url',
					method: 'POST',
					data: { url: targetUrl },
				} )
			);

			const status = result?.status || 'unknown';
			onStatusRef.current?.( {
				status,
				checkedAt: Date.now(),
				code: result?.code,
			} );
			lastCheckedUrl.current = targetUrl;
			return result;
		} catch ( err ) {
			// Auto-check failures stay quiet — only manual checks surface a notice.
			if ( ! silent ) {
				setError(
					err?.message ||
						__( 'Could not check this URL.', 'wp-usefull-blocks' )
				);
			}
			return null;
		} finally {
			setChecking( false );
		}
	};

	useEffect( () => {
		if ( ! enabled || ! url ) {
			return undefined;
		}

		if ( lastCheckedUrl.current === url ) {
			return undefined;
		}

		// Already have a known status for this URL — do not re-check on every
		// editor load (avoids parallel outbound HTTP storms).
		if ( initialStatus && 'unknown' !== initialStatus ) {
			lastCheckedUrl.current = url;
			return undefined;
		}

		// Fresh unknown with a recent check timestamp — skip.
		if (
			'unknown' === initialStatus &&
			lastChecked > 0 &&
			Date.now() - lastChecked < 5 * 60 * 1000
		) {
			lastCheckedUrl.current = url;
			return undefined;
		}

		const timer = setTimeout( () => {
			checkNow( url, { silent: true } );
		}, 400 );

		return () => clearTimeout( timer );
		// Intentionally only re-run when url / enabled change.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ url, enabled ] );

	return { checking, error, checkNow, clearError: () => setError( '' ) };
}
