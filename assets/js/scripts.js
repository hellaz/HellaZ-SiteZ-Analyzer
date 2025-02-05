const { registerBlockType } = wp.blocks;
const { TextControl } = wp.components;
const { useBlockProps } = wp.blockEditor;
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
        const blockProps = useBlockProps();

        // Debugging: Log attributes to the console
        console.log('Block Attributes:', attributes);

        return (
            <div {...blockProps}>
                <TextControl
                    label={__('Enter URL', 'hellaz-sitez-analyzer')}
                    value={attributes.url}
                    onChange={(value) => {
                        // Validate URL format
                        if (!value || value.match(/^https?:\/\/[^\s]+$/)) {
                            console.log('Setting URL attribute:', value); // Debugging
                            setAttributes({ url: value });
                        } else {
                            console.error(__('Invalid URL format:', 'hellaz-sitez-analyzer'), value);
                        }
                    }}
                    placeholder={__('https://example.com', 'hellaz-sitez-analyzer')}
                />
            </div>
        );
    },
    save: () => {
        // Server-side rendering is handled by PHP, so return null here.
        return null;
    },
});
