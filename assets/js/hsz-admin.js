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
