/**
 * Unit tests for the UB FAQ block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';
import {
	addItem,
	moveItem,
	normalizeItems,
	removeItem,
	updateItemField,
} from '../items';

describe( 'ub-faq block metadata', () => {
	it( 'uses the ub- naming convention and expected defaults', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-faq' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.category ).toBe( 'text' );
		expect( metadata.apiVersion ).toBe( 3 );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.viewScriptModule ).toBe( 'file:./view.js' );
		expect( metadata.supports.interactivity ).toBe( true );
		expect( metadata.attributes.items.default ).toEqual( [] );
		expect( metadata.attributes.initiallyOpen.default ).toBe( false );
		expect( metadata.keywords ).toEqual(
			expect.arrayContaining( [ 'faq', 'fragen' ] )
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

describe( 'ub-faq item helpers', () => {
	const sample = [
		{ id: 'a', question: 'One?', answer: 'First' },
		{ id: 'b', question: 'Two?', answer: 'Second' },
		{ id: 'c', question: 'Three?', answer: 'Third' },
	];

	it( 'normalizes incomplete items', () => {
		expect( normalizeItems( null ) ).toEqual( [] );
		expect( normalizeItems( [ { question: 'Only question' } ] ) ).toEqual( [
			{
				id: 'ub-faq-fallback-0',
				question: 'Only question',
				answer: '',
			},
		] );
	} );

	it( 'adds a blank entry with a stable id', () => {
		const next = addItem( sample );
		expect( next ).toHaveLength( 4 );
		expect( next[ 3 ].question ).toBe( '' );
		expect( next[ 3 ].answer ).toBe( '' );
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

	it( 'updates question and answer fields', () => {
		const asked = updateItemField( sample, 1, 'question', 'Updated?' );
		expect( asked[ 1 ].question ).toBe( 'Updated?' );
		expect( asked[ 1 ].answer ).toBe( 'Second' );

		const answered = updateItemField( sample, 0, 'answer', 'New copy' );
		expect( answered[ 0 ].answer ).toBe( 'New copy' );
		expect( updateItemField( sample, 0, 'unknown', 'x' ) ).toEqual(
			sample
		);
	} );
} );
