/**
 * Premium Updates - Checkout Scripts
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $form = $('#pus-checkout-form');
        var $submitBtn = $('#pus-submit-btn');
        var $btnText = $submitBtn.find('.pus-btn-text');
        var $btnLoading = $submitBtn.find('.pus-btn-loading');
        var $message = $('#pus-form-message');

        // Atualiza total ao mudar plano
        $('#pus-plan').on('change', function() {
            var price = $(this).find(':selected').data('price') || 0;
            $('#pus-total').text('R$ ' + formatPrice(price));
        });

        // Máscara CPF/CNPJ
        $('#pus-cpf').on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            
            if (value.length <= 11) {
                // CPF
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                // CNPJ
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            
            $(this).val(value);
        });

        // Submit do formulário
        $form.on('submit', function(e) {
            e.preventDefault();

            // Validação
            var errors = validateForm();
            
            if (errors.length > 0) {
                showMessage(errors.join('<br>'), 'error');
                return;
            }

            // Loading
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            $message.hide();

            $.ajax({
                url: pusCheckout.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pus_process_checkout',
                    nonce: pusCheckout.nonce,
                    plan_id: $('#pus-plan').val(),
                    client_name: $('#pus-name').val(),
                    client_email: $('#pus-email').val(),
                    cpf_cnpj: $('#pus-cpf').val(),
                    site_url: $('#pus-site').val()
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        
                        // Redireciona para o checkout do Asaas
                        if (response.data.checkout_url) {
                            setTimeout(function() {
                                window.location.href = response.data.checkout_url;
                            }, 1000);
                        }
                    } else {
                        showMessage(response.data, 'error');
                        resetButton();
                    }
                },
                error: function() {
                    showMessage(pusCheckout.strings.error, 'error');
                    resetButton();
                }
            });
        });

        function validateForm() {
            var errors = [];
            
            if (!$('#pus-plan').val()) {
                errors.push(pusCheckout.strings.required + ': Plano');
            }
            
            if (!$('#pus-name').val().trim()) {
                errors.push(pusCheckout.strings.required + ': Nome');
                $('#pus-name').addClass('error');
            } else {
                $('#pus-name').removeClass('error');
            }
            
            var email = $('#pus-email').val();
            if (!email || !isValidEmail(email)) {
                errors.push(pusCheckout.strings.invalid_email);
                $('#pus-email').addClass('error');
            } else {
                $('#pus-email').removeClass('error');
            }
            
            var cpf = $('#pus-cpf').val().replace(/\D/g, '');
            if (cpf.length !== 11 && cpf.length !== 14) {
                errors.push(pusCheckout.strings.invalid_cpf);
                $('#pus-cpf').addClass('error');
            } else {
                $('#pus-cpf').removeClass('error');
            }
            
            var url = $('#pus-site').val();
            if (!url || !isValidUrl(url)) {
                errors.push(pusCheckout.strings.invalid_url);
                $('#pus-site').addClass('error');
            } else {
                $('#pus-site').removeClass('error');
            }
            
            return errors;
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function isValidUrl(url) {
            try {
                new URL(url);
                return url.startsWith('http://') || url.startsWith('https://');
            } catch (e) {
                return false;
            }
        }

        function formatPrice(price) {
            return parseFloat(price).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function showMessage(msg, type) {
            $message.removeClass('success error').addClass(type).html(msg).show();
        }

        function resetButton() {
            $submitBtn.prop('disabled', false);
            $btnText.show();
            $btnLoading.hide();
        }
    });

})(jQuery);
