/**
 * Unit tests for the UB Callout block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';
import { isValidVariant, normalizeVariant } from '../variants';

describe( 'ub-callout block metadata', () => {
	it( 'uses the ub- naming convention and callout defaults', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-callout' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.category ).toBe( 'text' );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.attributes.variant.default ).toBe( 'info' );
		expect( metadata.attributes.showIcon.default ).toBe( true );
		expect( metadata.keywords ).toEqual(
			expect.arrayContaining( [ 'callout', 'hinweis' ] )
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

describe( 'ub-callout variants', () => {
	it( 'validates and normalizes variants', () => {
		expect( isValidVariant( 'tip' ) ).toBe( true );
		expect( isValidVariant( 'danger' ) ).toBe( false );
		expect( normalizeVariant( 'warning' ) ).toBe( 'warning' );
		expect( normalizeVariant( 'nope' ) ).toBe( 'info' );
	} );
} );
