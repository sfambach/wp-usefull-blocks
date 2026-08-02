/**
 * Unit tests for the "wp-usefull-blocks" block.
 */

// Mock the block editor package so the save component can be unit tested in
// isolation without pulling in the full editor runtime.
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: Object.assign( () => ( {} ), { save: () => ( {} ) } ),
} ) );

import metadata from '../block.json';
import save from '../save';

describe( 'wp-usefull-blocks block metadata', () => {
	it( 'exposes the expected block name and text domain', () => {
		expect( metadata.name ).toBe( 'wp-usefull-blocks/wp-usefull-blocks' );
		expect( metadata.textdomain ).toBe( 'wp-usefull-blocks' );
		expect( metadata.apiVersion ).toBe( 3 );
	} );
} );

describe( 'wp-usefull-blocks save output', () => {
	it( 'renders the saved front-end content', () => {
		const element = save();

		expect( element.type ).toBe( 'p' );
		expect( element.props.children ).toContain(
			'hello from the saved content'
		);
	} );
} );
