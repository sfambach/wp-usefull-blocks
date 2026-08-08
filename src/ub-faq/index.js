/**
 * Registers the UB FAQ block.
 */

import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
