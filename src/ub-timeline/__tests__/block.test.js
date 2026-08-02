/**
 * Unit tests for the UB Timeline block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';

describe( 'ub-timeline block metadata', () => {
	it( 'uses the ub- naming convention and widgets category', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-timeline' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.category ).toBe( 'widgets' );
		expect( metadata.apiVersion ).toBe( 3 );
		expect( metadata.title ).toBe( 'UB Timeline' );
	} );

	it( 'declares timeline attributes and interactivity support', () => {
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.viewScriptModule ).toBe( 'file:./view.js' );
		expect( metadata.supports.interactivity ).toBe( true );
		expect( metadata.attributes.orientation.default ).toBe( 'vertical' );
		expect( metadata.attributes.orientation.enum ).toEqual( [
			'vertical',
			'horizontal',
		] );
		expect( metadata.attributes.items.type ).toBe( 'array' );
		expect( metadata.attributes.items.default ).toEqual( [] );
		expect( metadata.attributes.initiallyOpen.default ).toBe( false );
		expect( metadata.supports.anchor ).toBe( true );
	} );

	it( 'registers as a dynamic block (save returns null)', () => {
		const indexSource = fs.readFileSync(
			path.join( __dirname, '../index.js' ),
			'utf8'
		);

		expect( indexSource ).toContain( 'save: () => null' );
	} );

	it( 'editor uses ServerSideRender for the shared preview', () => {
		const editSource = fs.readFileSync(
			path.join( __dirname, '../edit.js' ),
			'utf8'
		);

		expect( editSource ).toContain( '@wordpress/server-side-render' );
		expect( editSource ).toContain( 'wp-usefull-blocks/ub-timeline' );
		expect( editSource ).toContain( 'Add entry' );
	} );

	it( 'view script registers an Interactivity API toggle action', () => {
		const viewSource = fs.readFileSync(
			path.join( __dirname, '../view.js' ),
			'utf8'
		);

		expect( viewSource ).toContain( 'wp-usefull-blocks/ub-timeline' );
		expect( viewSource ).toContain( 'toggle' );
	} );
} );

/**
 * Normalize items to a safe array of objects.
 *
 * @param {Array} items Raw items attribute.
 * @return {Array} Normalized items.
 */
function normalizeItems( items ) {
	return Array.isArray( items ) ? items : [];
}

/**
 * Update one field on a timeline row.
 *
 * @param {Array}  items Raw items.
 * @param {number} index Row index.
 * @param {string} key   Field name.
 * @param {string} value New value.
 * @return {Array} Updated items.
 */
function updateItem( items, index, key, value ) {
	return normalizeItems( items ).map( ( item, itemIndex ) =>
		itemIndex === index
			? {
					...item,
					[ key ]: value,
			  }
			: item
	);
}

/**
 * Remove a timeline row by index.
 *
 * @param {Array}  items Raw items.
 * @param {number} index Row index.
 * @return {Array} Updated items.
 */
function removeItem( items, index ) {
	return normalizeItems( items ).filter(
		( _item, itemIndex ) => itemIndex !== index
	);
}

/**
 * Move a timeline row up or down.
 *
 * @param {Array}  items     Raw items.
 * @param {number} index     Row index.
 * @param {number} direction -1 for up, 1 for down.
 * @return {Array} Updated items.
 */
function moveItem( items, index, direction ) {
	const list = [ ...normalizeItems( items ) ];
	const target = index + direction;

	if ( target < 0 || target >= list.length ) {
		return list;
	}

	const [ moved ] = list.splice( index, 1 );
	list.splice( target, 0, moved );
	return list;
}

describe( 'ub-timeline item helpers', () => {
	const sample = [
		{ id: 'a', title: 'One', description: 'First' },
		{ id: 'b', title: 'Two', description: 'Second' },
		{ id: 'c', title: 'Three', description: 'Third' },
	];

	it( 'updates a single field on a row', () => {
		expect( updateItem( sample, 1, 'title', 'Updated' )[ 1 ].title ).toBe(
			'Updated'
		);
		expect( updateItem( sample, 1, 'title', 'Updated' )[ 0 ].title ).toBe(
			'One'
		);
	} );

	it( 'removes a row by index', () => {
		expect( removeItem( sample, 1 ).map( ( item ) => item.id ) ).toEqual( [
			'a',
			'c',
		] );
	} );

	it( 'reorders rows up and down', () => {
		expect( moveItem( sample, 1, -1 ).map( ( item ) => item.id ) ).toEqual(
			[ 'b', 'a', 'c' ]
		);
		expect( moveItem( sample, 0, -1 ).map( ( item ) => item.id ) ).toEqual(
			[ 'a', 'b', 'c' ]
		);
		expect( moveItem( sample, 2, 1 ).map( ( item ) => item.id ) ).toEqual( [
			'a',
			'b',
			'c',
		] );
	} );
} );
