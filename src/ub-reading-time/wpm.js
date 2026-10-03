/**
 * Helpers for UB Reading Time attributes.
 */

import { DEFAULT_WPM, MAX_WPM, MIN_WPM } from './constants';

/**
 * Clamp words-per-minute to the supported range.
 *
 * @param {number} wpm Candidate.
 * @return {number} Normalized WPM.
 */
export function normalizeWpm( wpm ) {
	const value = Number( wpm );
	if ( ! Number.isFinite( value ) ) {
		return DEFAULT_WPM;
	}
	if ( value < MIN_WPM ) {
		return DEFAULT_WPM;
	}
	if ( value > MAX_WPM ) {
		return MAX_WPM;
	}
	return Math.round( value );
}

export { DEFAULT_WPM, MAX_WPM, MIN_WPM };
