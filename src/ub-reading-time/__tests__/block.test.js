/**
 * Unit tests for the UB Reading Time block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';
import { DEFAULT_WPM, MAX_WPM, MIN_WPM, normalizeWpm } from '../wpm';

describe( 'ub-reading-time block metadata', () => {
	it( 'uses the ub- naming convention and reading-time defaults', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-reading-time' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.category ).toBe( 'widgets' );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.usesContext ).toEqual(
			expect.arrayContaining( [ 'postId', 'postType' ] )
		);
		expect( metadata.attributes.wordsPerMinute.default ).toBe(
			DEFAULT_WPM
		);
		expect( metadata.attributes.showWordCount.default ).toBe( false );
		expect( metadata.keywords ).toEqual(
			expect.arrayContaining( [ 'reading time', 'lesezeit' ] )
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

describe( 'ub-reading-time wpm helpers', () => {
	it( 'normalizes words-per-minute', () => {
		expect( normalizeWpm( 220 ) ).toBe( 220 );
		expect( normalizeWpm( MIN_WPM ) ).toBe( MIN_WPM );
		expect( normalizeWpm( MAX_WPM ) ).toBe( MAX_WPM );
		expect( normalizeWpm( 10 ) ).toBe( DEFAULT_WPM );
		expect( normalizeWpm( 9999 ) ).toBe( MAX_WPM );
		expect( normalizeWpm( Number.NaN ) ).toBe( DEFAULT_WPM );
	} );
} );
