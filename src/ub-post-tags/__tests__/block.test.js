/**
 * Unit tests for the UB Post Tags block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';

describe( 'ub-post-tags block metadata', () => {
	it( 'expands tag posts inline by default', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-post-tags' );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.viewScriptModule ).toBe( 'file:./view.js' );
		expect( metadata.attributes.expandInline.default ).toBe( true );
		expect( metadata.attributes.postsPerTag.default ).toBe( 10 );
	} );

	it( 'registers as a dynamic block (save returns null)', () => {
		const indexSource = fs.readFileSync(
			path.join( __dirname, '../index.js' ),
			'utf8'
		);

		expect( indexSource ).toContain( 'save: () => null' );
	} );
} );
