/**
 * Unit tests for the UB Timeline block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';
import {
	addItem,
	isValidOrientation,
	moveItem,
	normalizeItems,
	removeItem,
	updateItemField,
} from '../items';

describe( 'ub-timeline block metadata', () => {
	it( 'uses the ub- naming convention and expected defaults', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-timeline' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.category ).toBe( 'widgets' );
		expect( metadata.apiVersion ).toBe( 3 );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.viewScriptModule ).toBe( 'file:./view.js' );
		expect( metadata.supports.interactivity ).toBe( true );
		expect( metadata.attributes.orientation.default ).toBe( 'vertical' );
		expect( metadata.attributes.items.default ).toEqual( [] );
		expect( metadata.attributes.initiallyOpen.default ).toBe( false );
		expect( metadata.keywords ).toEqual(
			expect.arrayContaining( [ 'timeline', 'zeitleiste' ] )
		);
	} );

	it( 'registers as a dynamic block (save returns null)', () => {
		const indexSource = fs.readFileSync(
			path.join( __dirname, '../index.js' ),
			'utf8'
		);

		expect( indexSource ).toContain( 'save: () => null' );
	} );
} );

describe( 'ub-timeline item helpers', () => {
	const sample = [
		{ id: 'a', title: 'One', description: 'First' },
		{ id: 'b', title: 'Two', description: 'Second' },
		{ id: 'c', title: 'Three', description: 'Third' },
	];

	it( 'normalizes incomplete items', () => {
		expect( normalizeItems( null ) ).toEqual( [] );
		expect( normalizeItems( [ { title: 'Only title' } ] ) ).toEqual( [
			{
				id: 'ub-tl-fallback-0',
				title: 'Only title',
				description: '',
			},
		] );
	} );

	it( 'adds a blank entry with a stable id', () => {
		const next = addItem( sample );
		expect( next ).toHaveLength( 4 );
		expect( next[ 3 ].title ).toBe( '' );
		expect( next[ 3 ].description ).toBe( '' );
		expect( next[ 3 ].id ).toEqual( expect.any( String ) );
		expect( next[ 3 ].id.length ).toBeGreaterThan( 3 );
	} );

	it( 'removes and reorders entries', () => {
		expect( removeItem( sample, 1 ).map( ( item ) => item.id ) ).toEqual( [
			'a',
			'c',
		] );
		expect( moveItem( sample, 0, 1 ).map( ( item ) => item.id ) ).toEqual( [
			'b',
			'a',
			'c',
		] );
		expect( moveItem( sample, 2, -1 ).map( ( item ) => item.id ) ).toEqual(
			[ 'a', 'c', 'b' ]
		);
		expect( moveItem( sample, 0, -1 ) ).toEqual( sample );
	} );

	it( 'updates title and description fields', () => {
		const titled = updateItemField( sample, 1, 'title', 'Updated' );
		expect( titled[ 1 ].title ).toBe( 'Updated' );
		expect( titled[ 1 ].description ).toBe( 'Second' );

		const described = updateItemField(
			sample,
			0,
			'description',
			'New copy'
		);
		expect( described[ 0 ].description ).toBe( 'New copy' );
		expect( updateItemField( sample, 0, 'unknown', 'x' ) ).toEqual(
			sample
		);
	} );

	it( 'validates orientation values', () => {
		expect( isValidOrientation( 'vertical' ) ).toBe( true );
		expect( isValidOrientation( 'horizontal' ) ).toBe( true );
		expect( isValidOrientation( 'diagonal' ) ).toBe( false );
	} );
} );
