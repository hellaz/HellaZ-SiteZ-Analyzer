(function(blocks, element, blockEditor, components, i18n) {
    const { registerBlockType } = blocks;
    const { createElement } = element;
    const { InspectorControls, useBlockProps } = blockEditor;
    const { PanelBody, TextControl, SelectControl, Button, Spinner, Notice } = components;
    const { __ } = i18n;
    const { useState } = element;

    registerBlockType('hsz/analyzer-block', {
        title: __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'),
        description: __('Analyze and display website metadata, social links, and security information', 'hellaz-sitez-analyzer'),
        icon: 'search',
        category: 'widgets',
        keywords: [
            __('website', 'hellaz-sitez-analyzer'),
            __('metadata', 'hellaz-sitez-analyzer'),
            __('analyze', 'hellaz-sitez-analyzer'),
            __('social', 'hellaz-sitez-analyzer')
        ],
        attributes: {
            url: { type: 'string', default: '' },
            displayType: { type: 'string', default: 'full' }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { url, displayType } = attributes;
            const blockProps = useBlockProps();
            const [analysisData, setAnalysisData] = useState(null);
            const [isLoading, setIsLoading] = useState(false);
            const [error, setError] = useState('');

            const analyzeUrl = function() {
                if (!url || !url.trim()) {
                    setError(__('Please enter a URL', 'hellaz-sitez-analyzer'));
                    return;
                }
                setIsLoading(true);
                setError('');
                setAnalysisData(null);

                wp.ajax.post('hsz_analyze_url', {
                    url: url.trim(),
                    nonce: hsz_block_params?.nonce || ''
                }).done(function(response) {
                    setAnalysisData(response);
                    setIsLoading(false);
                }).fail(function(xhr) {
                    let errorMessage = __('Failed to analyze URL', 'hellaz-sitez-analyzer');
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = xhr.responseJSON.data;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                    setError(errorMessage);
                    setIsLoading(false);
                });
            };

            const renderPreview = function() {
                if (isLoading) {
                    return createElement('div', { className: 'hsz-loading' },
                        createElement(Spinner),
                        createElement('p', null, __('Analyzing URL...', 'hellaz-sitez-analyzer'))
                    );
                }
                if (error) {
                    return createElement(Notice, { status: 'error', isDismissible: false }, error);
                }
                if (!analysisData) {
                    return createElement('div', { className: 'hsz-placeholder' },
                        createElement('p', null, __('Enter a URL and click "Analyze" to see the preview', 'hellaz-sitez-analyzer'))
                    );
                }
                return createElement('div', { className: 'hsz-preview' },
                    analysisData.title && createElement('h4', null, analysisData.title),
                    analysisData.description && createElement('p', null, analysisData.description),
                    analysisData.favicon && createElement('img', {
                        src: analysisData.favicon,
                        alt: 'Favicon',
                        className: 'hsz-favicon'
                    }),
                    analysisData.social_media && Object.keys(analysisData.social_media).length > 0 &&
                    createElement('div', { className: 'hsz-social-preview' },
                        createElement('strong', null, __('Social Media:', 'hellaz-sitez-analyzer') + ' '),
                        Object.keys(analysisData.social_media).join(', ')
                    )
                );
            };

            return [
                createElement(InspectorControls, { key: 'inspector' },
                    createElement(PanelBody, {
                        title: __('Analyzer Settings', 'hellaz-sitez-analyzer'),
                        initialOpen: true
                    },
                        createElement(TextControl, {
                            label: __('Website URL', 'hellaz-sitez-analyzer'),
                            value: url,
                            onChange: (val) => setAttributes({ url: val }),
                            placeholder: __('https://example.com', 'hellaz-sitez-analyzer'),
                            help: __('Enter the URL you want to analyze', 'hellaz-sitez-analyzer')
                        }),
                        createElement(SelectControl, {
                            label: __('Display Type', 'hellaz-sitez-analyzer'),
                            value: displayType,
                            options: [
                                { label: __('Full Analysis', 'hellaz-sitez-analyzer'), value: 'full' },
                                { label: __('Metadata Only', 'hellaz-sitez-analyzer'), value: 'metadata' },
                                { label: __('Social Media Only', 'hellaz-sitez-analyzer'), value: 'social' }
                            ],
                            onChange: (val) => setAttributes({ displayType: val }),
                            help: __('Choose what information to display', 'hellaz-sitez-analyzer')
                        }),
                        createElement(Button, {
                            isPrimary: true,
                            onClick: analyzeUrl,
                            disabled: !url || isLoading,
                            isBusy: isLoading
                        }, isLoading ? __('Analyzing...', 'hellaz-sitez-analyzer') : __('Analyze URL', 'hellaz-sitez-analyzer'))
                    )
                ),
                createElement('div', Object.assign({ key: 'block' }, blockProps),
                    createElement('div', { className: 'hsz-block-editor' },
                        createElement('div', { className: 'hsz-block-header' },
                            createElement('h3', null, __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer')),
                            url && createElement('p', { className: 'hsz-current-url' }, __('URL:', 'hellaz-sitez-analyzer') + ' ' + url)
                        ),
                        renderPreview()
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
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);
