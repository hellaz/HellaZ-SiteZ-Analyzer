const { registerBlockType } = wp.blocks;
const { TextControl } = wp.components;
const { useBlockProps } = wp.blockEditor;
const { __ } = wp.i18n; // Import wp.i18n for translations

registerBlockType('hsz/metadata-block', {
    title: __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'), // Translatable title
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
        const blockProps = useBlockProps();

        // Debugging: Log attributes to the console
        console.log('Block Attributes:', attributes);

        return (
            <div {...blockProps}>
                <TextControl
                    label={__('Enter URL', 'hellaz-sitez-analyzer')} // Translatable label
                    value={attributes.url}
                    onChange={(value) => {
                        // Validate URL format
                        if (!value || value.match(/^https?:\/\/[^\s]+$/)) {
                            setAttributes({ url: value });
                        } else {
                            console.error(__('Invalid URL format:', 'hellaz-sitez-analyzer'), value);
                        }
                    }}
                    placeholder={__('https://example.com', 'hellaz-sitez-analyzer')} // Translatable placeholder
                />
            </div>
        );
    },
    save: () => {
        // Server-side rendering is handled by PHP, so return null here.
        return null;
    },
});
