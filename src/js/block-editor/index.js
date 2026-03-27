/* global outstandSilentUpdate */
/**
 * WordPress dependencies.
 */
import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies.
 */
import SilentUpdate from './components/SilentUpdate';

registerPlugin('outstand-silent-update', {
	render: () => {
		const postType = useSelect(
			(select) => select(editorStore).getCurrentPostType(),
			[],
		);
		const supportedPostTypes =
			outstandSilentUpdate?.postTypes ?? [];

		if (!postType || !supportedPostTypes.includes(postType)) {
			return null;
		}

		return <SilentUpdate />;
	},
});
