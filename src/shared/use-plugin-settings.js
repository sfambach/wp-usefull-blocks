/**
 * Read plugin settings injected via block_editor_settings_all.
 */

import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

const DEFAULTS = {
	show_link_status: true,
	strike_broken_links: true,
	auto_check_urls: true,
};

/**
 * @return {{show_link_status:boolean,strike_broken_links:boolean,auto_check_urls:boolean}} Settings.
 */
export default function usePluginSettings() {
	return useSelect( ( select ) => {
		const settings = select( blockEditorStore ).getSettings?.() || {};
		const plugin = settings.wpUsefullBlocks || {};
		return {
			show_link_status:
				plugin.show_link_status ?? DEFAULTS.show_link_status,
			strike_broken_links:
				plugin.strike_broken_links ?? DEFAULTS.strike_broken_links,
			auto_check_urls: plugin.auto_check_urls ?? DEFAULTS.auto_check_urls,
		};
	}, [] );
}
