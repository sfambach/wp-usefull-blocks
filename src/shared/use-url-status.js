/**
 * Auto / manual URL status checks against the plugin REST route.
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * @param {Object}   args
 * @param {string}   args.url
 * @param {boolean}  args.enabled
 * @param {string}   args.initialStatus
 * @param {Function} args.onStatus
 * @return {{checking:boolean,error:string,checkNow:Function}} Status helpers.
 */
export default function useUrlStatus( {
	url,
	enabled,
	initialStatus = 'unknown',
	onStatus,
} ) {
	const [ checking, setChecking ] = useState( false );
	const [ error, setError ] = useState( '' );
	const lastCheckedUrl = useRef( '' );

	const checkNow = async ( targetUrl = url ) => {
		setError( '' );

		if ( ! targetUrl ) {
			return null;
		}

		setChecking( true );

		try {
			const result = await apiFetch( {
				path: '/wp-usefull-blocks/v1/check-url',
				method: 'POST',
				data: { url: targetUrl },
			} );

			const status = result?.status || 'unknown';
			onStatus?.( {
				status,
				checkedAt: Date.now(),
				code: result?.code,
			} );
			lastCheckedUrl.current = targetUrl;
			return result;
		} catch ( err ) {
			setError(
				err?.message ||
					__( 'Could not check this URL.', 'wp-usefull-blocks' )
			);
			return null;
		} finally {
			setChecking( false );
		}
	};

	useEffect( () => {
		if ( ! enabled || ! url ) {
			return undefined;
		}

		if ( lastCheckedUrl.current === url && initialStatus !== 'unknown' ) {
			return undefined;
		}

		const timer = setTimeout( () => {
			checkNow( url );
		}, 400 );

		return () => clearTimeout( timer );
		// Intentionally only re-run when url / enabled change.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ url, enabled ] );

	return { checking, error, checkNow };
}
