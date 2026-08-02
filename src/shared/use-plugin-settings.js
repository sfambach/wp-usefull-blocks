/**
 * Read plugin settings injected via block_editor_settings_all.
 */

import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

const DEFAULTS = {
	show_link_status: true,
	link_status_position: 'before',
	strike_broken_links: true,
	auto_check_urls: true,
};

/**
 * Normalize traffic-light position.
 *
 * @param {string} position Raw position.
 * @return {'before'|'after'|'off'} Position.
 */
function normalizePosition( position ) {
	if ( 'after' === position || 'off' === position ) {
		return position;
	}
	return 'before';
}

/**
 * @return {{show_link_status:boolean,link_status_position:string,strike_broken_links:boolean,auto_check_urls:boolean}} Settings.
 */
export default function usePluginSettings() {
	return useSelect( ( select ) => {
		const settings = select( blockEditorStore ).getSettings?.() || {};
		const plugin = settings.wpUsefullBlocks || {};
		const position = normalizePosition(
			plugin.link_status_position ?? DEFAULTS.link_status_position
		);

		return {
			show_link_status: 'off' !== position,
			link_status_position: position,
			strike_broken_links:
				plugin.strike_broken_links ?? DEFAULTS.strike_broken_links,
			auto_check_urls: plugin.auto_check_urls ?? DEFAULTS.auto_check_urls,
		};
	}, [] );
}
