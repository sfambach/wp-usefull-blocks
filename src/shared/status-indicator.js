/**
 * Shared traffic-light status indicator for the block editor.
 */

import { __ } from '@wordpress/i18n';

const LABELS = {
	ok: __( 'Link status: OK', 'wp-usefull-blocks' ),
	broken: __( 'Link status: Broken', 'wp-usefull-blocks' ),
	unknown: __( 'Link status: Unknown', 'wp-usefull-blocks' ),
};

/**
 * @param {Object} props
 * @param {string} [props.status]
 * @return {Element|null} Indicator element.
 */
export default function StatusIndicator( { status = 'unknown' } ) {
	const key = [ 'ok', 'broken', 'unknown' ].includes( status )
		? status
		: 'unknown';
	const label = LABELS[ key ];

	return (
		<span className={ `ub-status ub-status--${ key }` } title={ label }>
			<span className="ub-status__icon" aria-hidden="true">
				●
			</span>
			<span className="screen-reader-text">{ label }</span>
		</span>
	);
}
