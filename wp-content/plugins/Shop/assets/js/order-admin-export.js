/**
 * Order Admin – Production Files export (Original SVG, Cut-Ready SVG, Vector PDF, Test Inkscape).
 * Expects: window.apdOrderExport = { nonce: string, ajaxurl: string }; and jQuery.
 */
(function() {
    'use strict';

    var config = window.apdOrderExport || {};
    var nonce = config.nonce || '';
    var ajaxurl = config.ajaxurl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

    function getNonce() {
        return nonce;
    }

    window.testInkscape = function() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apd_test_inkscape',
                _wpnonce: getNonce()
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Inkscape Status: WORKING!\n\n' +
                        'Path: ' + response.data.inkscape_path + '\n' +
                        'Version: ' + response.data.version + '\n\n' +
                        '✅ PDF export will use server-side Inkscape processing.\n' +
                        '✅ Material outlines will be preserved for CorelDRAW.');
                } else {
                    alert('❌ Inkscape Not Available\n\n' +
                        response.data.message + '\n\n' +
                        'Shell_exec: ' + (response.data.shell_exec || '') + '\n\n' +
                        'Recommendation:\n' + (response.data.recommendation || ''));
                }
            },
            error: function() {
                alert('Error checking Inkscape status');
            }
        });
    };

    window.downloadOrderSVG = function(orderId) {
        var button = event && event.target ? event.target.closest('button') : null;
        var originalText = button ? button.innerHTML : '';
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> Processing...';
        }

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'download_order_svg',
                order_id: orderId
            },
            success: function(response) {
                if (response.success && response.data.file_url) {
                    var a = document.createElement('a');
                    a.href = response.data.file_url;
                    a.download = response.data.filename || ('order-' + orderId + '-design.svg');
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    if (button && button.closest('.order-svg-download-section')) {
                        var div = document.createElement('div');
                        div.className = 'notice notice-success is-dismissible';
                        div.innerHTML = '<p><strong>✅ Success!</strong> ' + (response.data.message || '') + '</p>';
                        button.closest('.order-svg-download-section').appendChild(div);
                        setTimeout(function() { div.remove(); }, 5000);
                    }
                } else {
                    alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Failed to download SVG'));
                }
            },
            error: function() {
                alert('Network error occurred while downloading SVG');
            },
            complete: function() {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            }
        });
    };

    window.processCutReadySVG = function(orderId) {
        var button = event && event.target ? event.target.closest('button') : null;
        var originalText = button ? button.innerHTML : '';
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> Processing...';
        }

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apd_process_cut_ready_svg',
                order_id: orderId,
                _wpnonce: getNonce()
            },
            success: function(response) {
                if (response.success && response.data.file_url) {
                    var a = document.createElement('a');
                    a.href = response.data.file_url;
                    a.download = response.data.filename || ('order-' + orderId + '-cut-ready.svg');
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    if (button && button.closest('.order-svg-download-section')) {
                        var div = document.createElement('div');
                        div.className = 'notice notice-success is-dismissible';
                        div.innerHTML = '<p><strong>✅ Success!</strong> ' + (response.data.message || '') + '</p><p style="margin: 5px 0 0 0;"><small>File saved: ' + (response.data.filename || '') + '</small></p>';
                        button.closest('.order-svg-download-section').appendChild(div);
                        setTimeout(function() { div.remove(); }, 8000);
                    }
                } else {
                    alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Failed to process SVG'));
                }
            },
            error: function(xhr, status, error) {
                alert('Network error occurred while processing SVG: ' + (error || status));
            },
            complete: function() {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            }
        });
    };

    window.exportCDRVector = function(orderId) {
        var button = event && event.target ? event.target.closest('button') : null;
        var originalText = button ? button.innerHTML : '';
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> Processing...';
        }

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apd_export_cdr_vector',
                order_id: orderId,
                _wpnonce: getNonce()
            },
            success: function(response) {
                if (response.success && response.data.file_url) {
                    var a = document.createElement('a');
                    a.href = response.data.file_url;
                    a.download = response.data.filename || ('order-' + orderId + '-design-vector-for-coreldraw.svg');
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    if (button && button.closest('.order-svg-download-section')) {
                        var div = document.createElement('div');
                        div.className = 'notice notice-success is-dismissible';
                        div.innerHTML = '<p><strong>✅ Success!</strong> ' + (response.data.message || '') + '</p><p style="margin: 5px 0 0 0;"><small>File saved: ' + (response.data.filename || '') + '</small></p>';
                        button.closest('.order-svg-download-section').appendChild(div);
                        setTimeout(function() { div.remove(); }, 8000);
                    }
                } else {
                    alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Failed to export CDR vector file'));
                }
            },
            error: function(xhr, status, error) {
                alert('Network error while exporting CDR vector file: ' + (error || status));
            },
            complete: function() {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            }
        });
    };

    window.exportVectorPDF = window.exportVectorPDF || function() {
        console.warn('PDF export: load full order-admin script or use inline exportVectorPDF.');
    };
})();
