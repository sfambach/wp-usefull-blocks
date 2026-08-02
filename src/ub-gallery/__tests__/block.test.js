/**
 * Unit tests for the UB Gallery block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';

describe( 'ub-gallery block metadata', () => {
	it( 'uses the ub- naming convention and media category', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-gallery' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.category ).toBe( 'media' );
		expect( metadata.apiVersion ).toBe( 3 );
	} );

	it( 'declares a dynamic interactive block with gallery attributes', () => {
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.viewScriptModule ).toBe( 'file:./view.js' );
		expect( metadata.supports.interactivity ).toBe( true );
		expect( metadata.attributes.images.type ).toBe( 'array' );
		expect( metadata.attributes.onImageClick.default ).toBe( 'lightbox' );
		expect( metadata.attributes.onImageClick.enum ).toEqual( [
			'lightbox',
			'media',
			'none',
		] );
	} );

	it( 'registers as a dynamic block (save returns null)', () => {
		const indexSource = fs.readFileSync(
			path.join( __dirname, '../index.js' ),
			'utf8'
		);

		expect( indexSource ).toContain( 'save: () => null' );
	} );
} );
