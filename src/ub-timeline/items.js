/**
 * Pure helpers for UB Timeline entry list mutations.
 */

/**
 * Create a stable unique id for a timeline entry.
 *
 * @return {string} Unique id.
 */
export function createItemId() {
	if (
		typeof crypto !== 'undefined' &&
		typeof crypto.randomUUID === 'function'
	) {
		return crypto.randomUUID();
	}

	return `ub-tl-${ Date.now().toString( 36 ) }-${ Math.random()
		.toString( 36 )
		.slice( 2, 9 ) }`;
}

/**
 * Normalize a raw items array from block attributes.
 *
 * @param {Array} items Raw items.
 * @return {Array<{id:string,title:string,description:string}>} Normalized items.
 */
export function normalizeItems( items ) {
	if ( ! Array.isArray( items ) ) {
		return [];
	}

	return items.map( ( item, index ) => {
		const raw = item && typeof item === 'object' ? item : {};
		const id =
			typeof raw.id === 'string' && raw.id
				? raw.id
				: `ub-tl-fallback-${ index }`;

		return {
			id,
			title: typeof raw.title === 'string' ? raw.title : '',
			description:
				typeof raw.description === 'string' ? raw.description : '',
		};
	} );
}

/**
 * Append an empty entry.
 *
 * @param {Array} items Current items.
 * @return {Array} Next items.
 */
export function addItem( items ) {
	const next = normalizeItems( items );
	next.push( {
		id: createItemId(),
		title: '',
		description: '',
	} );
	return next;
}

/**
 * Remove the entry at index.
 *
 * @param {Array}  items Current items.
 * @param {number} index Index to remove.
 * @return {Array} Next items.
 */
export function removeItem( items, index ) {
	const next = normalizeItems( items );
	if ( index < 0 || index >= next.length ) {
		return next;
	}
	next.splice( index, 1 );
	return next;
}

/**
 * Move an entry up or down.
 *
 * @param {Array}  items Current items.
 * @param {number} index Index to move.
 * @param {number} delta -1 (up) or +1 (down).
 * @return {Array} Next items.
 */
export function moveItem( items, index, delta ) {
	const next = normalizeItems( items );
	const target = index + delta;

	if (
		index < 0 ||
		index >= next.length ||
		target < 0 ||
		target >= next.length
	) {
		return next;
	}

	const [ entry ] = next.splice( index, 1 );
	next.splice( target, 0, entry );
	return next;
}

/**
 * Update a single field on an entry.
 *
 * @param {Array}  items Current items.
 * @param {number} index Entry index.
 * @param {string} field Field name (`title` or `description`).
 * @param {string} value New value.
 * @return {Array} Next items.
 */
export function updateItemField( items, index, field, value ) {
	const next = normalizeItems( items );

	if ( index < 0 || index >= next.length ) {
		return next;
	}

	if ( 'title' !== field && 'description' !== field ) {
		return next;
	}

	next[ index ] = {
		...next[ index ],
		[ field ]: typeof value === 'string' ? value : '',
	};

	return next;
}

/**
 * Whether orientation is a supported value.
 *
 * @param {string} orientation Candidate.
 * @return {boolean} True when valid.
 */
export function isValidOrientation( orientation ) {
	return 'vertical' === orientation || 'horizontal' === orientation;
}
