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

        return wp.element.createElement(
            'div',
            blockProps,
            wp.element.createElement(TextControl, {
                label: __('Enter URL', 'hellaz-sitez-analyzer'),
                value: attributes.url,
                onChange: (value) => {
                    // Save the URL attribute immediately
                    setAttributes({ url: value });
                },
                onBlur: (event) => {
                    const value = event.target.value;
                    if (!value.match(/^https?:\/\/[^\s]+$/)) {
                        console.error(__('Invalid URL format:', 'hellaz-sitez-analyzer'), value);
                    }
                },
                placeholder: __('https://example.com', 'hellaz-sitez-analyzer'),
                __nextHasNoMarginBottom: true,
            })
        );
    },
    save: () => {
        return null; // Server-side rendering is handled by PHP
    },
});
