/**
 * Unit tests for the UB File block.
 */

import metadata from '../block.json';

describe( 'ub-file block metadata', () => {
	it( 'uses the ub- naming convention and manual mirror attributes', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/ub-file' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.render ).toBe( 'file:./render.php' );
		expect( metadata.attributes.sourceUrl.type ).toBe( 'string' );
		expect( metadata.attributes.attachmentId.type ).toBe( 'number' );
		expect( metadata.attributes.mirroredFromUrl.type ).toBe( 'string' );
		expect( metadata.attributes.linkBehavior.default ).toBe(
			'original-fallback-local'
		);
	} );
} );
