const { registerBlockType } = wp.blocks;
const { TextControl } = wp.components;
const { createElement } = wp.element;
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

        // Validate URL input
        const isValidUrl = (url) => {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        };

        return createElement('div', {},
            createElement(TextControl, {
                label: __('Website URL', 'hellaz-sitez-analyzer'),
                value: attributes.url,
                onChange: (value) => {
                    if (isValidUrl(value) || value === '') {
                        setAttributes({ url: value });
                    }
                },
                help: !isValidUrl(attributes.url) && attributes.url !== ''
                    ? __('Please enter a valid URL.', 'hellaz-sitez-analyzer')
                    : null,
            }),
            // Optional: Preview in the editor
            attributes.url && isValidUrl(attributes.url) &&
            createElement('p', { style: { marginTop: '10px' } },
                __('Preview:', 'hellaz-sitez-analyzer'),
                createElement('br'),
                createElement('code', {}, attributes.url)
            )
        );
    },
    save: () => {
        // Server-side rendering, so return null
        return null;
    },
});
