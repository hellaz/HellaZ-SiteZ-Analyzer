(function(blocks, element, editor, components, i18n) {
    const { registerBlockType } = blocks;
    const { createElement } = element;
    const { RichText, InspectorControls, PanelColorSettings } = editor;
    const { PanelBody, TextControl, SelectControl, Button, Spinner } = components;
    const { __ } = i18n;

    registerBlockType('hsz/analyzer-block', {
        title: __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'),
        description: __('Display website metadata analysis', 'hellaz-sitez-analyzer'),
        icon: 'search',
        category: 'widgets',
        keywords: [
            __('website', 'hellaz-sitez-analyzer'),
            __('metadata', 'hellaz-sitez-analyzer'),
            __('analyze', 'hellaz-sitez-analyzer')
        ],
        attributes: {
            url: {
                type: 'string',
                default: ''
            },
            displayType: {
                type: 'string',
                default: 'full'
            },
            previewData: {
                type: 'object',
                default: null
            }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { url, displayType, previewData } = attributes;

            const onChangeUrl = function(newUrl) {
                setAttributes({ url: newUrl, previewData: null });
            };

            const onChangeDisplayType = function(newType) {
                setAttributes({ displayType: newType });
            };

            const analyzeUrl = function() {
                if (!url) return;

                setAttributes({ previewData: { loading: true } });

                wp.ajax.post('hsz_analyze_url', {
                    url: url,
                    nonce: hsz_block_params.nonce
                }).done(function(response) {
                    setAttributes({ previewData: response });
                }).fail(function(response) {
                    setAttributes({ 
                        previewData: { 
                            error: response.responseText || __('Analysis failed', 'hellaz-sitez-analyzer') 
                        } 
                    });
                });
            };

            return [
                createElement(InspectorControls, null,
                    createElement(PanelBody, {
                        title: __('Analyzer Settings', 'hellaz-sitez-analyzer'),
                        initialOpen: true
                    },
                        createElement(TextControl, {
                            label: __('Website URL', 'hellaz-sitez-analyzer'),
                            value: url,
                            onChange: onChangeUrl,
                            placeholder: __('Enter website URL...', 'hellaz-sitez-analyzer')
                        }),
                        createElement(SelectControl, {
                            label: __('Display Type', 'hellaz-sitez-analyzer'),
                            value: displayType,
                            options: [
                                { label: __('Full Analysis', 'hellaz-sitez-analyzer'), value: 'full' },
                                { label: __('Metadata Only', 'hellaz-sitez-analyzer'), value: 'metadata' },
                                { label: __('Social Links Only', 'hellaz-sitez-analyzer'), value: 'social' }
                            ],
                            onChange: onChangeDisplayType
                        }),
                        createElement(Button, {
                            isPrimary: true,
                            onClick: analyzeUrl,
                            disabled: !url
                        }, __('Analyze URL', 'hellaz-sitez-analyzer'))
                    )
                ),
                createElement('div', { className: 'hsz-block-editor' },
                    !url ? 
                        createElement('div', { className: 'hsz-placeholder' },
                            createElement('p', null, __('Enter a URL in the sidebar to analyze a website.', 'hellaz-sitez-analyzer'))
                        ) :
                        previewData && previewData.loading ?
                            createElement('div', { className: 'hsz-loading' },
                                createElement(Spinner),
                                createElement('p', null, __('Analyzing website...', 'hellaz-sitez-analyzer'))
                            ) :
                            previewData && previewData.error ?
                                createElement('div', { className: 'hsz-error' },
                                    createElement('p', null, previewData.error)
                                ) :
                                previewData ?
                                    createElement('div', { className: 'hsz-preview' },
                                        createElement('h4', null, previewData.title || url),
                                        previewData.description && createElement('p', null, previewData.description),
                                        createElement('small', null, 
                                            createElement('a', { href: url, target: '_blank' }, url)
                                        )
                                    ) :
                                    createElement('div', { className: 'hsz-placeholder' },
                                        createElement('p', null, __('Click "Analyze URL" to preview the website analysis.', 'hellaz-sitez-analyzer'))
                                    )
                )
            ];
        },
        save: function() {
            return null; // Server-side rendering
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.editor,
    window.wp.components,
    window.wp.i18n
);
