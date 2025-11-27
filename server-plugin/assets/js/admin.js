/**
 * Premium Updates Server - Admin Scripts
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Copy button functionality
        $('.pus-copy-btn').on('click', function() {
            var textToCopy = $(this).data('copy');
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Copiado para a área de transferência!');
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(textToCopy).select();
                document.execCommand('copy');
                $temp.remove();
                alert('Copiado para a área de transferência!');
            }
        });

        // Auto-generate slug from name
        $('#plugin_name').on('blur', function() {
            var $slug = $('#plugin_slug');
            if (!$slug.val() && !$slug.prop('readonly')) {
                var name = $(this).val();
                var slug = name.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                $slug.val(slug);
            }
        });

        // Confirm delete
        $('.button-link-delete').on('click', function(e) {
            if (!confirm($(this).data('confirm') || 'Tem certeza?')) {
                e.preventDefault();
            }
        });
    });

})(jQuery);
