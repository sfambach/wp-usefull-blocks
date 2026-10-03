/**
 * Pure helpers for UB Callout variants.
 */

export const CALLOUT_VARIANTS = [ 'info', 'tip', 'warning', 'success' ];

/**
 * @param {string} variant Candidate.
 * @return {boolean} True when supported.
 */
export function isValidVariant( variant ) {
	return CALLOUT_VARIANTS.includes( variant );
}

/**
 * @param {string} variant Variant slug.
 * @return {string} Safe variant.
 */
export function normalizeVariant( variant ) {
	return isValidVariant( variant ) ? variant : 'info';
}
