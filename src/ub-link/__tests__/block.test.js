/**
 * Unit tests for the UB Link block.
 */

import metadata from '../block.json';

describe( 'ub-link block metadata', () => {
	it( 'uses the ub- naming convention', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-link' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.attributes.url.type ).toBe( 'string' );
		expect( metadata.attributes.lastStatus.default ).toBe( 'unknown' );
	} );
} );
