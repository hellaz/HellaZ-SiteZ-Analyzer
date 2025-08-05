/**
 * HellaZ SiteZ Analyzer - Admin AJAX Scripts
 *
 * Handles AJAX requests from the plugin's admin dashboard for URL analysis.
 *
 * @version 1.0.1
 * @author HellaZ
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Localized parameters passed from PHP.
		const params = window.hsz_admin_params || {
			error_url_empty: 'Please provide a URL to analyze.',
			text_analyzing: 'Analyzing...',
			text_loading: 'Fetching data...',
			text_analysis_complete: 'Analysis Complete',
			error_generic: 'An unknown error occurred.',
			error_ajax: 'The request failed. Please check your connection and try again.',
			error_urls_empty: 'Please enter at least one URL for bulk processing.',
			text_processing: 'Processing...',
			text_bulk_success: 'Bulk processing started successfully. Batch ID: %s'
		};

		/**
		 * Handle Single URL Analysis Form Submission
		 */
		$('#hsz-analyze-form').on('submit', function(e) {
			e.preventDefault();

			const $form = $(this);
			const $button = $form.find('input[type="submit"]');
			const $resultContainer = $('#hsz-analysis-result');
			const originalButtonText = $button.val();
			const url = $('#hsz-url').val().trim();
			const nonce = $('#hsz_nonce').val();

			if (!url) {
				$resultContainer.html('<div class="notice notice-error"><p>' + params.error_url_empty + '</p></div>');
				return;
			}

			// Show loading state
			$button.val(params.text_analyzing).prop('disabled', true);
			$resultContainer.html('<div class="hsz-loading"><span class="spinner is-active"></span><p>' + params.text_loading + '</p></div>');

			$.post(ajaxurl, {
					action: 'hsz_analyze_url', // CORRECTED: Matches the AJAX handler in PHP
					url: url,
					_wpnonce: nonce // CORRECTED: Use _wpnonce to match check_ajax_referer
				})
				.done(function(response) {
					if (response.success && response.data && response.data.metadata) {
						const data = response.data.metadata;
						let html = '<h4>' + params.text_analysis_complete + '</h4>';
						html += '<div class="hsz-results-card">';
						html += '<h5>' + (data.title || 'No Title Found') + '</h5>';
						html += '<p>' + (data.description || 'No Description Found') + '</p>';
						html += '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
						html += '</div>';
						$resultContainer.html(html);
					} else {
						const errorMessage = (response.data && response.data.message) ? response.data.message : params.error_generic;
						$resultContainer.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
					}
				})
				.fail(function() {
					$resultContainer.html('<div class="notice notice-error"><p>' + params.error_ajax + '</p></div>');
				})
				.always(function() {
					// Restore button
					$button.val(originalButtonText).prop('disabled', false);
				});
		});

		/**
		 * Handle Bulk URL Processing Form Submission
		 */
		$('#hsz-bulk-form').on('submit', function(e) {
			e.preventDefault();

			const $form = $(this);
			const $button = $form.find('input[type="submit"]');
			const $statusContainer = $('#hsz-bulk-status');
			const originalButtonText = $button.val();

			const batchName = $('#hsz-batch-name').val().trim();
			const urls = $('#hsz-urls').val().split(/\r?\n/).filter(Boolean);
			const nonce = $('#hsz_bulk_nonce').val();

			if (urls.length === 0) {
				$statusContainer.html('<div class="notice notice-error"><p>' + params.error_urls_empty + '</p></div>');
				return;
			}

			// Show loading state
			$button.val(params.text_processing).prop('disabled', true);
			$statusContainer.html('');

			$.post(ajaxurl, {
					action: 'hsz_start_bulk_processing',
					batch_name: batchName,
					urls: urls,
					_wpnonce: nonce // CORRECTED: Use _wpnonce
				})
				.done(function(response) {
					if (response.success) {
						const successMessage = params.text_bulk_success.replace('%s', response.data.batch_id);
						$statusContainer.html('<div class="notice notice-success is-dismissible"><p>' + successMessage + '</p></div>');
						$('#hsz-urls').val(''); // Clear textarea
					} else {
						const errorMessage = (response.data && response.data.message) ? response.data.message : params.error_generic;
						$statusContainer.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
					}
				})
				.fail(function() {
					$statusContainer.html('<div class="notice notice-error"><p>' + params.error_ajax + '</p></div>');
				})
				.always(function() {
					$button.val(originalButtonText).prop('disabled', false);
				});
		});
	});

})(jQuery);

/**
 * Enhanced Admin JavaScript for HellaZ SiteZ Analyzer
 * Handles template selection, API testing, and admin interactions
 */

jQuery(document).ready(function($) {
    'use strict';

    // Template selection interactions
    $('.hsz-template-option').on('click', function(e) {
        // Don't trigger if clicking on the radio button directly
        if (e.target.type !== 'radio') {
            $(this).find('input[type="radio"]').prop('checked', true);
        }
        
        $('.hsz-template-option').removeClass('hsz-selected');
        $(this).addClass('hsz-selected');
    });

    // Template option hover effects
    $('.hsz-template-option').hover(
        function() {
            $(this).addClass('hsz-template-hover');
        },
        function() {
            $(this).removeClass('hsz-template-hover');
        }
    );

    // API Testing functionality
    $('.hsz-test-api').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var apiType = $button.data('api');
        var $input = $button.prev('input[type="password"]');
        var apiKey = $input.val();

        if (!apiKey) {
            alert(hszAdminEnhanced.i18n.api_failed + ' API key is required.');
            return;
        }

        // Update button state
        $button.prop('disabled', true)
               .removeClass('success error')
               .addClass('testing')
               .text(hszAdminEnhanced.i18n.testing_api);

        // AJAX request to test API
        $.ajax({
            url: hszAdminEnhanced.ajaxurl,
            type: 'POST',
            data: {
                action: 'hsz_test_api',
                nonce: hszAdminEnhanced.nonce,
                api_type: apiType,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    $button.removeClass('testing error')
                           .addClass('success')
                           .text('✓ ' + hszAdminEnhanced.i18n.api_success);
                    
                    // Show success message
                    showMessage(response.data, 'success');
                } else {
                    $button.removeClass('testing success')
                           .addClass('error')
                           .text('✗ API Failed');
                    
                    // Show error message
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                $button.removeClass('testing success')
                       .addClass('error')
                       .text('✗ Connection Error');
                
                showMessage('Connection failed. Please try again.', 'error');
            },
            complete: function() {
                // Reset button after 3 seconds
                setTimeout(function() {
                    $button.prop('disabled', false)
                           .removeClass('testing success error')
                           .text('Test API');
                }, 3000);
            }
        });
    });

    // Clear Cache functionality
    $('.hsz-clear-cache-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        
        if (!confirm(hszAdminEnhanced.i18n.clearing_cache + ' Continue?')) {
            return;
        }

        $button.prop('disabled', true)
               .addClass('hsz-loading')
               .text(hszAdminEnhanced.i18n.clearing_cache);

        $.ajax({
            url: hszAdminEnhanced.ajaxurl,
            type: 'POST',
            data: {
                action: 'hsz_clear_cache_ajax',
                nonce: hszAdminEnhanced.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(hszAdminEnhanced.i18n.cache_cleared, 'success');
                } else {
                    showMessage(response.data || 'Cache clearing failed.', 'error');
                }
            },
            error: function() {
                showMessage('Failed to clear cache. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false)
                       .removeClass('hsz-loading')
                       .text('Clear Cache');
            }
        });
    });

    // Reset Settings functionality
    $('.hsz-reset-settings-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        
        if (!confirm(hszAdminEnhanced.i18n.confirm_reset)) {
            return;
        }

        $button.prop('disabled', true)
               .addClass('hsz-loading')
               .text('Resetting...');

        $.ajax({
            url: hszAdminEnhanced.ajaxurl,
            type: 'POST',
            data: {
                action: 'hsz_reset_settings',
                nonce: hszAdminEnhanced.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Settings reset successfully. Page will reload.', 'success');
                    // Reload page after 2 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage(response.data || 'Settings reset failed.', 'error');
                }
            },
            error: function() {
                showMessage('Failed to reset settings. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false)
                       .removeClass('hsz-loading')
                       .text('Reset Settings');
            }
        });
    });

    // Save Settings enhancement with feedback
    $('.hsz-save-settings').on('click', function() {
        var $button = $(this);
        
        // Add loading state
        $button.addClass('hsz-loading');
        
        // Remove loading state after form submission
        setTimeout(function() {
            $button.removeClass('hsz-loading');
        }, 2000);
    });

    // Show message function
    function showMessage(message, type) {
        // Remove existing messages
        $('.hsz-message').remove();
        
        // Create message element
        var $message = $('<div class="hsz-message ' + type + '">' + message + '</div>');
        
        // Insert after the admin header
        $('.hsz-admin-header').after($message);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Form validation enhancement
    $('form.hsz-settings-form').on('submit', function() {
        var hasErrors = false;
        
        // Basic validation for required fields
        $(this).find('input[required]').each(function() {
            if (!$(this).val()) {
                $(this).css('border-color', '#d63638');
                hasErrors = true;
            } else {
                $(this).css('border-color', '');
            }
        });
        
        if (hasErrors) {
            showMessage('Please fill in all required fields.', 'error');
            return false;
        }
        
        return true;
    });

    // Smooth scroll for internal links
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 50
            }, 500);
        }
    });

    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[title]').tooltip({
            placement: 'top',
            trigger: 'hover'
        });
    }

    // Auto-save draft functionality for text areas
    $('textarea').on('input', function() {
        var $textarea = $(this);
        var fieldName = $textarea.attr('name');
        
        // Debounced auto-save (wait 2 seconds after last input)
        clearTimeout($textarea.data('timeout'));
        $textarea.data('timeout', setTimeout(function() {
            // Auto-save logic here if needed
            console.log('Auto-saving field: ' + fieldName);
        }, 2000));
    });
});

// Additional utility functions
window.HSZ_Admin = {
    showMessage: function(message, type) {
        jQuery('.hsz-message').remove();
        var $message = jQuery('<div class="hsz-message ' + type + '">' + message + '</div>');
        jQuery('.hsz-admin-header').after($message);
        setTimeout(function() {
            $message.fadeOut(function() {
                jQuery(this).remove();
            });
        }, 5000);
    },
    
    testAPI: function(apiType, apiKey) {
        // Programmatic API testing function
        jQuery.ajax({
            url: hszAdminEnhanced.ajaxurl,
            type: 'POST',
            data: {
                action: 'hsz_test_api',
                nonce: hszAdminEnhanced.nonce,
                api_type: apiType,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    HSZ_Admin.showMessage(response.data, 'success');
                } else {
                    HSZ_Admin.showMessage(response.data, 'error');
                }
            }
        });
    }
};
