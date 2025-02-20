const { registerBlockType } = wp.blocks;
const { TextControl } = wp.components;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { useState } = wp.element;
const { __ } = wp.i18n;

registerBlockType('hsz/metadata-block', {
    title: __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'),
    icon: 'admin-site',
    category: 'widgets',
    attributes: {
        url: {
            type: 'string',
            default: '',
        },
    },
    edit: (props) => {
        const { attributes, setAttributes } = props;
        const [previewData, setPreviewData] = useState(null);

        const fetchPreview = async (url) => {
            if (!url) {
                setPreviewData(null);
                return;
            }

            try {
                const response = await fetch(`/wp-json/hsz/v1/metadata/${encodeURIComponent(url)}`);
                if (!response.ok) {
                    throw new Error('Failed to fetch metadata');
                }
                const data = await response.json();
                setPreviewData(data);
            } catch (error) {
                console.error('Error fetching preview:', error);
                setPreviewData({ error: __('An error occurred while fetching metadata.', 'hellaz-sitez-analyzer') });
            }
        };

        return (
            <div {...useBlockProps()}>
                <InspectorControls>
                    <TextControl
                        label={__('Enter URL', 'hellaz-sitez-analyzer')}
                        value={attributes.url}
                        onChange={(value) => {
                            setAttributes({ url: value });
                            fetchPreview(value);
                        }}
                        placeholder={__('https://example.com', 'hellaz-sitez-analyzer')}
                    />
                </InspectorControls>

                {attributes.url ? (
                    previewData ? (
                        previewData.error ? (
                            <p>{previewData.error}</p>
                        ) : (
                            <div>
                                <h3>{__('Metadata Preview', 'hellaz-sitez-analyzer')}</h3>
                                <ul>
                                    {Object.entries(previewData).map(([key, value]) => (
                                        <li key={key}>
                                            <strong>{key}:</strong> {value}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )
                    ) : (
                        <p>{__('Loading preview...', 'hellaz-sitez-analyzer')}</p>
                    )
                ) : (
                    <p>{__('Please enter a URL to preview metadata.', 'hellaz-sitez-analyzer')}</p>
                )}
            </div>
        );
    },
    save: () => {
        return null; // Server-side rendering is handled by PHP
    },
});
