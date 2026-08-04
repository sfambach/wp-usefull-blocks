/**
 * Unit tests for the UB Table of Contents block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';
import { clampLevel, normalizeLevelRange } from '../levels';

describe( 'ub-toc block metadata', () => {
	it( 'uses the ub- naming convention and toc defaults', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-toc' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.attributes.minLevel.default ).toBe( 2 );
		expect( metadata.attributes.maxLevel.default ).toBe( 3 );
		expect( metadata.usesContext ).toEqual(
			expect.arrayContaining( [ 'postId' ] )
		);
		expect( metadata.keywords ).toEqual(
			expect.arrayContaining( [ 'toc', 'inhaltsverzeichnis' ] )
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

describe( 'ub-toc level helpers', () => {
	it( 'clamps and normalizes level ranges', () => {
		expect( clampLevel( 0 ) ).toBe( 1 );
		expect( clampLevel( 9 ) ).toBe( 6 );
		expect( normalizeLevelRange( 4, 2 ) ).toEqual( {
			minLevel: 2,
			maxLevel: 4,
		} );
	} );
} );
