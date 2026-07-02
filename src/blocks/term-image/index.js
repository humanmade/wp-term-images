import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Placeholder,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

function Edit( { attributes, setAttributes } ) {
	const { termId, taxonomy, imageSize } = attributes;
	const blockProps = useBlockProps();

	const taxonomies = useSelect( ( select ) => {
		return select( coreStore ).getTaxonomies( {
			per_page: -1,
			show_in_rest: true,
		} );
	}, [] );

	const terms = useSelect(
		( select ) => {
			if ( ! taxonomy ) {
				return null;
			}
			return select( coreStore ).getEntityRecords( 'taxonomy', taxonomy, {
				per_page: -1,
				_fields: [ 'id', 'name' ],
			} );
		},
		[ taxonomy ]
	);

	const imageSizes = useSelect(
		( select ) => select( blockEditorStore ).getSettings().imageSizes,
		[]
	);

	const taxonomyOptions = taxonomies
		? [
				{
					label: __( '— Select taxonomy —', 'wp-term-images' ),
					value: '',
				},
				...taxonomies.map( ( tax ) => ( {
					label: tax.name,
					value: tax.slug,
				} ) ),
		  ]
		: [];

	const termOptions = terms
		? [
				{ label: __( '— Select term —', 'wp-term-images' ), value: 0 },
				...terms.map( ( term ) => ( {
					label: term.name,
					value: term.id,
				} ) ),
		  ]
		: [];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Term Image', 'wp-term-images' ) }>
					{ ! taxonomies ? (
						<Spinner />
					) : (
						<SelectControl
							label={ __( 'Taxonomy', 'wp-term-images' ) }
							value={ taxonomy }
							options={ taxonomyOptions }
							onChange={ ( value ) =>
								setAttributes( { taxonomy: value, termId: 0 } )
							}
						/>
					) }
					{ taxonomy && ! terms && <Spinner /> }
					{ taxonomy && terms && (
						<SelectControl
							label={ __( 'Term', 'wp-term-images' ) }
							value={ termId }
							options={ termOptions }
							onChange={ ( value ) =>
								setAttributes( { termId: Number( value ) } )
							}
						/>
					) }
					{ imageSizes && imageSizes.length > 0 && (
						<SelectControl
							label={ __( 'Image Size', 'wp-term-images' ) }
							value={ imageSize }
							options={ imageSizes.map( ( size ) => ( {
								label: size.name,
								value: size.slug,
							} ) ) }
							onChange={ ( value ) =>
								setAttributes( { imageSize: value } )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ termId ? (
					<p
						style={ {
							margin: 0,
							padding: '1em',
							fontStyle: 'italic',
						} }
					>
						{ __(
							'Term image preview unavailable in editor.',
							'wp-term-images'
						) }
					</p>
				) : (
					<Placeholder
						icon="format-image"
						label={ __( 'Term Image', 'wp-term-images' ) }
						instructions={ __(
							'Select a taxonomy and term in the block settings panel.',
							'wp-term-images'
						) }
					/>
				) }
			</div>
		</>
	);
}

registerBlockType( metadata, {
	edit: Edit,
	save: () => null,
} );
