document.addEventListener('DOMContentLoaded', function () {
    console.log('HellaZ SiteZ Analyzer scripts loaded.');
});

const { registerBlockType } = wp.blocks;
const { TextControl } = wp.components;
const { createElement } = wp.element;

registerBlockType('hsz/metadata-block', {
    title: 'HellaZ SiteZ Analyzer',
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

        return createElement('div', {},
            createElement(TextControl, {
                label: 'Website URL',
                value: attributes.url,
                onChange: (value) => setAttributes({ url: value }),
            })
        );
    },
    save: () => {
        // Server-side rendering, so return null
        return null;
    },
});
