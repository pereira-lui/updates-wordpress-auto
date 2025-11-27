/**
 * Premium Updates Client - Admin Scripts
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Test connection button
        $('#puc-test-connection').on('click', function() {
            var $button = $(this);
            var $result = $('#puc-test-result');
            
            var serverUrl = $('#puc_server_url').val();
            var licenseKey = $('#puc_license_key').val();
            
            if (!serverUrl || !licenseKey) {
                $result.removeClass('success').addClass('error').text('Preencha a URL e a chave de licença');
                return;
            }
            
            $button.prop('disabled', true);
            $result.removeClass('success error').text(pucAdmin.strings.testing);
            
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_test_connection',
                    nonce: pucAdmin.nonce,
                    server_url: serverUrl,
                    license_key: licenseKey
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').text(response.data);
                    } else {
                        $result.removeClass('success').addClass('error').text(response.data);
                    }
                },
                error: function() {
                    $result.removeClass('success').addClass('error').text('Erro de conexão');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Sync plugins button
        $('#puc-sync-plugins').on('click', function() {
            var $button = $(this);
            var $result = $('#puc-sync-result');
            
            $button.prop('disabled', true);
            $result.removeClass('success error').text(pucAdmin.strings.syncing);
            
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_sync_plugins',
                    nonce: pucAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').text(response.data.message);
                        
                        // Destaca os plugins disponíveis no servidor
                        if (response.data.plugins && response.data.plugins.length > 0) {
                            var serverSlugs = response.data.plugins.map(function(p) { return p.slug; });
                            
                            $('.puc-plugins-table tbody tr').each(function() {
                                var $row = $(this);
                                var slug = $row.find('.puc-plugin-slug').text();
                                
                                if (serverSlugs.indexOf(slug) !== -1) {
                                    $row.css('background-color', '#f0fff0');
                                }
                            });
                        }
                    } else {
                        $result.removeClass('success').addClass('error').text(response.data);
                    }
                },
                error: function() {
                    $result.removeClass('success').addClass('error').text('Erro de conexão');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Select all checkbox
        $('#puc-select-all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.puc-plugins-table tbody input[type="checkbox"]').prop('checked', isChecked);
        });
    });

})(jQuery);
