/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginSidebar, store as editorStore } from '@wordpress/editor';
import {
	PanelBody,
	Button,
	__experimentalText as Text,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { dateI18n, getSettings } from '@wordpress/date';
import { store as editPostStore } from '@wordpress/edit-post';
import { PinnedItems } from '@wordpress/interface';

const SIDEBAR_NAME =
	'outstand-silent-update/outstand-silent-update';

/**
 * Format a date string using the site's configured format and timezone.
 *
 * @param {string} date ISO date string.
 * @return {string} Formatted date string.
 */
const formatDate = (date) => {
	const { formats, timezone } = getSettings();
	return dateI18n(formats.datetime, date, timezone.offset);
};

const SilentUpdate = () => {
	const { isSaving, isSaveable, isLocked, isPublished, modifiedDate } = useSelect(
		(select) => {
			const store = select(editorStore);

			return {
				isSaving: store.isSavingPost(),
				isSaveable: store.isEditedPostSaveable(),
				isLocked: store.isPostSavingLocked(),
				isPublished: store.getEditedPostAttribute('status') === 'publish',
				modifiedDate: store.getEditedPostAttribute('modified'),
			};
		},
		[],
	);

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return -- hook must be called before early return
	const isSidebarOpen = useSelect(
		(select) =>
			select(editPostStore).getActiveGeneralSidebarName?.() ===
			SIDEBAR_NAME,
		[],
	);

	const [currentDate, setCurrentDate] = useState(
		() => new Date().toISOString(),
	);

	useEffect(() => {
		if (isSidebarOpen) {
			setCurrentDate(new Date().toISOString());
		}
	}, [isSidebarOpen]);

	const { editPost, savePost } = useDispatch(editorStore);
	const { openGeneralSidebar, closeGeneralSidebar } =
		useDispatch(editPostStore); // eslint-disable-line @wordpress/no-unused-vars-before-return -- hooks must be called before early return

	if (!isPublished) {
		return null;
	}

	const isActionDisabled = isSaving || !isSaveable || isLocked;

	const handleSilentUpdate = async () => {
		editPost({ update_type: 'silent' });

		try {
			await savePost();
		} finally {
			openGeneralSidebar('edit-post/document');
		}
	};

	return (
		<>
			<PinnedItems scope="core">
				<Button
					onClick={() =>
						isSidebarOpen
							? closeGeneralSidebar()
							: openGeneralSidebar(SIDEBAR_NAME)
					}
					disabled={isActionDisabled}
					aria-expanded={isSidebarOpen}
					isPressed={isSidebarOpen}
					variant="secondary"
					size="compact"
					showTooltip
					__next40pxDefaultSize
				>
					{__('Silent Update', 'outstand-silent-update')}
				</Button>
			</PinnedItems>
			<PluginSidebar
				name="outstand-silent-update"
				title={__('Silent Update', 'outstand-silent-update')}
				isPinnable={false}
			>
				<PanelBody>
					<VStack spacing={4}>
						<Text>
							{__(
								'Save your changes without updating the last modified date.',
								'outstand-silent-update',
							)}
						</Text>
						<VStack spacing={1}>
							<Text weight={600}>
								{__('Current Date', 'outstand-silent-update')}
							</Text>
							<Text>{formatDate(currentDate)}</Text>
						</VStack>
						<VStack spacing={1}>
							<Text weight={600}>
								{__('Last Modified (preserved)', 'outstand-silent-update')}
							</Text>
							<Text>{formatDate(modifiedDate)}</Text>
						</VStack>
						<HStack>
							<Button
								disabled={isActionDisabled}
								isBusy={isSaving}
								onClick={handleSilentUpdate}
								variant="secondary"
								__next40pxDefaultSize
							>
								{__('Save silently', 'outstand-silent-update')}
							</Button>
						</HStack>
					</VStack>
				</PanelBody>
			</PluginSidebar>
		</>
	);
};

export default SilentUpdate;
