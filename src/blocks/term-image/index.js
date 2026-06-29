import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

registerBlockType( metadata, {
	edit() {
		const blockProps = useBlockProps();
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="format-image"
					label={ __( 'Term Image', 'wp-term-images' ) }
					instructions={ __(
						'Displays the featured image for the assigned taxonomy term.',
						'wp-term-images'
					) }
				/>
			</div>
		);
	},
	save: () => null,
} );
