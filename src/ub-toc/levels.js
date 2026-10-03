/**
 * Helpers for TOC level attributes.
 */

/**
 * Clamp a heading level to 1–6.
 *
 * @param {number} level Raw level.
 * @return {number} Clamped level.
 */
export function clampLevel( level ) {
	const value = Number( level );
	if ( Number.isNaN( value ) ) {
		return 2;
	}
	return Math.min( 6, Math.max( 1, Math.round( value ) ) );
}

/**
 * Normalize min/max so min <= max.
 *
 * @param {number} minLevel Min.
 * @param {number} maxLevel Max.
 * @return {{ minLevel: number, maxLevel: number }} Pair.
 */
export function normalizeLevelRange( minLevel, maxLevel ) {
	let min = clampLevel( minLevel );
	let max = clampLevel( maxLevel );
	if ( min > max ) {
		const swap = min;
		min = max;
		max = swap;
	}
	return { minLevel: min, maxLevel: max };
}
