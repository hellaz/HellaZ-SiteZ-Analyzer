jQuery(document).ready(function($) {
    $('#hsz-analyze-form').on('submit', function(e) {
        e.preventDefault();
        var url = $('#hsz-url').val().trim();
        var nonce = $('#hsz_nonce').val();
        $.post(ajaxurl, {
            action: 'hsz_process_single_url',
            url: url,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $('#hsz-analysis-result').html('<pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
            } else {
                $('#hsz-analysis-result').html('<span style="color:red;">' + response.data + '</span>');
            }
        });
    });
    $('#hsz-bulk-form').on('submit', function(e) {
        e.preventDefault();
        var batchName = $('#hsz-batch-name').val().trim();
        var urls = $('#hsz-urls').val().split(/\r?\n/).filter(Boolean);
        var nonce = $('#hsz_bulk_nonce').val();
        $.post(ajaxurl, {
            action: 'hsz_start_bulk_processing',
            batch_name: batchName,
            urls: urls,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $('#hsz-bulk-status').html('Bulk processing started. Batch ID: ' + response.data.batch_id);
            } else {
                $('#hsz-bulk-status').html('<span style="color:red;">' + response.data + '</span>');
            }
        });
    });
});
