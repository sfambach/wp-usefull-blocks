/**
 * Unit tests for the UB Related Posts block.
 */

import fs from 'fs';
import path from 'path';
import metadata from '../block.json';

describe( 'ub-related-posts block metadata', () => {
	it( 'keeps the display-posts defaults', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-related-posts' );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.attributes.taxonomy.default ).toBe( 'category' );
		expect( metadata.attributes.includeChildren.default ).toBe( true );
		expect( metadata.attributes.excludeCurrent.default ).toBe( true );
		expect( metadata.attributes.count.default ).toBe( 10 );
		expect( metadata.attributes.showImage.default ).toBe( false );
	} );

	it( 'registers as a dynamic block (save returns null)', () => {
		const indexSource = fs.readFileSync(
			path.join( __dirname, '../index.js' ),
			'utf8'
		);

		expect( indexSource ).toContain( 'save: () => null' );
	} );
} );
