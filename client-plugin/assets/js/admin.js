/**
 * Premium Updates Client - Admin Scripts
 */

(function($) {
    'use strict';

    var pricesLoaded = false;
    var accountLoaded = false;
    var paymentsLoaded = false;
    var updatesLoaded = false;

    $(document).ready(function() {
        
        // Tab navigation
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.puc-tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
            
            // Update URL hash
            window.location.hash = tab;
            
            // Load prices when subscription tab is opened
            if (tab === 'subscription' && !pricesLoaded) {
                loadPrices();
            }
            
            // Load account data
            if (tab === 'account' && !accountLoaded) {
                loadAccountData();
            }
            
            // Load payments
            if (tab === 'payments' && !paymentsLoaded) {
                loadPayments();
            }
            
            // Load updates history
            if (tab === 'updates-history' && !updatesLoaded) {
                loadUpdatesHistory();
            }
        });
        
        // Check hash on load
        if (window.location.hash) {
            var hash = window.location.hash.substring(1);
            $('.nav-tab[data-tab="' + hash + '"]').click();
        }

        // Test connection button
        $('#puc-test-connection').on('click', function() {
            var $button = $(this);
            var $result = $('#puc-test-result');
            
            var serverUrl = $('#puc_server_url').val();
            var licenseKey = $('#puc_license_key').val();
            
            if (!serverUrl) {
                $result.removeClass('success').addClass('error').text('Preencha a URL do servidor');
                return;
            }
            
            $button.prop('disabled', true);
            $result.removeClass('success error').html('<span class="puc-spinner"></span> ' + pucAdmin.strings.testing);
            
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
                        $result.removeClass('error').addClass('success').text(response.data.message);
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
            $result.removeClass('success error').html('<span class="puc-spinner"></span> ' + pucAdmin.strings.syncing);
            
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

        // Refresh license status
        $('#puc-refresh-license').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true).text(pucAdmin.strings.loading);
            
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_check_license',
                    nonce: pucAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data || 'Erro ao verificar licença');
                        $button.prop('disabled', false).text('Atualizar Status');
                    }
                },
                error: function() {
                    alert('Erro de conexão');
                    $button.prop('disabled', false).text('Atualizar Status');
                }
            });
        });

        // Load prices from server
        function loadPrices() {
            var serverUrl = $('#puc_server_url').val();
            
            if (!serverUrl) {
                $('#puc-prices-loading').hide();
                return;
            }
            
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_get_prices',
                    nonce: pucAdmin.nonce,
                    server_url: serverUrl
                },
                success: function(response) {
                    if (response.success) {
                        pricesLoaded = true;
                        renderPricingCards(response.data);
                    } else {
                        $('#puc-prices-loading').html('<p class="notice notice-error">' + (response.data || 'Erro ao carregar preços') + '</p>');
                    }
                },
                error: function() {
                    $('#puc-prices-loading').html('<p class="notice notice-error">Erro de conexão com o servidor</p>');
                }
            });
        }
        
        // Render pricing cards
        function renderPricingCards(prices) {
            var html = '';
            
            for (var period in prices) {
                var price = prices[period];
                var duration = price.days > 0 ? price.days + ' dias' : 'Para sempre';
                
                html += '<div class="puc-pricing-card" data-period="' + period + '">' +
                    '<h3>' + price.label + '</h3>' +
                    '<div class="puc-price">' +
                        '<span class="puc-currency">R$</span>' +
                        '<span class="puc-amount">' + formatMoney(price.price) + '</span>' +
                    '</div>' +
                    '<p class="puc-duration">' + duration + '</p>' +
                    '<button type="button" class="button button-primary puc-select-plan" data-period="' + period + '">Escolher</button>' +
                '</div>';
            }
            
            $('#puc-prices-loading').hide();
            $('#puc-pricing-cards').html(html).show();
            
            // Bind click events
            $('.puc-select-plan').on('click', function() {
                var period = $(this).data('period');
                
                $('.puc-pricing-card').removeClass('selected');
                $(this).closest('.puc-pricing-card').addClass('selected');
                
                $('#puc-selected-period').val(period);
                $('#puc-subscription-form').slideDown();
                
                $('html, body').animate({
                    scrollTop: $('#puc-subscription-form').offset().top - 50
                }, 500);
            });
        }
        
        // Format money
        function formatMoney(value) {
            return parseFloat(value).toFixed(2).replace('.', ',');
        }

        // Submit new subscription
        $('#puc-checkout-form').on('submit', function(e) {
            e.preventDefault();
            
            var $btn = $('#puc-submit-subscription');
            var $result = $('#puc-payment-result');
            var originalText = $btn.text();
            
            // Validate
            var name = $('#puc-customer-name').val();
            var email = $('#puc-customer-email').val();
            var document = $('#puc-customer-cpf').val();
            var period = $('#puc-selected-period').val();
            var paymentMethod = $('#puc-payment-method').val();
            var generateInvoice = $('#puc-generate-invoice').is(':checked') ? 1 : 0;
            
            if (!name || !email || !document || !period) {
                alert('Preencha todos os campos obrigatórios');
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="puc-spinner"></span> ' + pucAdmin.strings.processing);
            
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_create_subscription',
                    nonce: pucAdmin.nonce,
                    server_url: $('#puc_server_url').val(),
                    name: name,
                    email: email,
                    document: document,
                    period: period,
                    payment_method: paymentMethod,
                    generate_invoice: generateInvoice
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        var data = response.data;
                        
                        if (data.pix && data.pix.qrcode) {
                            html = '<div class="puc-pix-result">' +
                                '<h3>Pagamento via PIX</h3>' +
                                '<p>Escaneie o QR Code abaixo ou copie o código PIX:</p>' +
                                '<img src="data:image/png;base64,' + data.pix.qrcode + '" alt="QR Code PIX">' +
                                '<div class="puc-pix-code" id="puc-pix-code">' + data.pix.payload + '</div>' +
                                '<button type="button" class="button puc-copy-btn" id="puc-copy-pix">Copiar Código</button>' +
                                '<p style="margin-top:15px;"><strong>Aguardando pagamento...</strong></p>' +
                                '<p class="description">Após o pagamento, sua licença será ativada automaticamente.</p>' +
                                '<button type="button" class="button" id="puc-check-payment" data-payment-id="' + data.payment_id + '">Verificar Pagamento</button>' +
                                '</div>';
                        } else if (data.boleto_url) {
                            html = '<div class="notice notice-success" style="padding:15px;">' +
                                '<h3>Boleto Gerado!</h3>' +
                                '<p><a href="' + data.boleto_url + '" target="_blank" class="button button-primary">Visualizar Boleto</a></p>' +
                                '<p class="description">Após o pagamento, sua licença será ativada em até 3 dias úteis.</p>' +
                                '<button type="button" class="button" id="puc-check-payment" data-payment-id="' + data.payment_id + '" style="margin-top:10px;">Verificar Pagamento</button>' +
                                '</div>';
                        }
                        
                        $('#puc-subscription-form').slideUp();
                        $result.html(html).slideDown();
                        
                        // Bind copy button
                        $('#puc-copy-pix').on('click', function() {
                            var code = $('#puc-pix-code').text();
                            navigator.clipboard.writeText(code).then(function() {
                                alert(pucAdmin.strings.copy_success);
                            });
                        });
                        
                        // Bind check payment button
                        $('#puc-check-payment').on('click', function() {
                            var paymentId = $(this).data('payment-id');
                            checkPaymentStatus(paymentId);
                        });
                    } else {
                        alert(response.data || 'Erro ao processar assinatura');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Erro de conexão. Tente novamente.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Renew subscription
        $('#puc-renew-form').on('submit', function(e) {
            e.preventDefault();
            
            var $btn = $(this).find('button[type="submit"]');
            var $result = $('#puc-renew-result');
            var originalText = $btn.text();
            var generateInvoice = $('#puc-renew-invoice').is(':checked') ? 1 : 0;
            
            $btn.prop('disabled', true).html('<span class="puc-spinner"></span> ' + pucAdmin.strings.processing);
            
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_renew_subscription',
                    nonce: pucAdmin.nonce,
                    period: $('#puc-renew-period').val(),
                    payment_method: $('#puc-renew-method').val(),
                    generate_invoice: generateInvoice
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        var data = response.data;
                        
                        if (data.pix && data.pix.qrcode) {
                            html = '<div class="puc-pix-result" style="margin-top:20px;">' +
                                '<h4>Pagamento via PIX</h4>' +
                                '<img src="data:image/png;base64,' + data.pix.qrcode + '" alt="QR Code" style="max-width:200px;">' +
                                '<div class="puc-pix-code" id="puc-renew-pix-code">' + data.pix.payload + '</div>' +
                                '<button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById(\'puc-renew-pix-code\').textContent);alert(\'' + pucAdmin.strings.copy_success + '\');">Copiar</button>' +
                                '<p style="margin-top:10px;"><button type="button" class="button" id="puc-check-renew-payment" data-payment-id="' + data.payment_id + '">Verificar Pagamento</button></p>' +
                                '</div>';
                        } else if (data.boleto_url) {
                            html = '<p style="margin-top:15px;"><a href="' + data.boleto_url + '" target="_blank" class="button">Visualizar Boleto</a></p>';
                        } else {
                            html = '<p class="notice notice-success" style="margin-top:15px;padding:10px;">Pagamento aprovado! Recarregue a página.</p>';
                        }
                        $result.html(html);
                        
                        // Bind check payment
                        $('#puc-check-renew-payment').on('click', function() {
                            checkPaymentStatus($(this).data('payment-id'));
                        });
                    } else {
                        alert(response.data || 'Erro ao renovar');
                    }
                    $btn.prop('disabled', false).text(originalText);
                },
                error: function() {
                    alert('Erro de conexão');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Check payment status
        function checkPaymentStatus(paymentId) {
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_check_payment',
                    nonce: pucAdmin.nonce,
                    payment_id: paymentId
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.status === 'confirmed' || response.data.status === 'paid') {
                            alert('Pagamento confirmado! Sua licença foi ativada.');
                            window.location.reload();
                        } else if (response.data.status === 'pending') {
                            alert('Pagamento ainda pendente. Aguarde a confirmação.');
                        } else {
                            alert('Status: ' + response.data.status);
                        }
                    } else {
                        alert(response.data || 'Erro ao verificar pagamento');
                    }
                },
                error: function() {
                    alert('Erro de conexão');
                }
            });
        }

        // Mask for CPF/CNPJ
        $('#puc-customer-cpf').on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            
            if (value.length <= 11) {
                // CPF
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                // CNPJ
                value = value.replace(/(\d{2})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1/$2');
                value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }
            
            $(this).val(value);
        });

        // Load account data
        function loadAccountData() {
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_get_account',
                    nonce: pucAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        accountLoaded = true;
                        renderAccountData(response.data);
                    } else {
                        $('#puc-account-loading').hide();
                        $('#puc-account-error').html('<p>' + (response.data || 'Erro ao carregar dados da conta') + '</p>').show();
                    }
                },
                error: function() {
                    $('#puc-account-loading').hide();
                    $('#puc-account-error').html('<p>Erro de conexão com o servidor</p>').show();
                }
            });
        }

        // Render account data
        function renderAccountData(data) {
            var license = data.license || {};
            var stats = data.stats || {};
            var plugins = data.plugins || [];
            var recentActivity = data.recent_activity || [];

            // Status badge
            var statusClass = license.status === 'active' ? 'active' : 'expired';
            var statusLabel = license.status_label || license.status;
            $('#puc-account-status-badge').text(statusLabel).addClass(statusClass);
            $('.puc-license-card').addClass(statusClass);

            // License info
            $('#puc-account-license-key').text(license.license_key || '-');
            $('#puc-account-period').text(license.period_label || '-');
            $('#puc-account-expires').text(license.expires_at ? formatDate(license.expires_at) : 'Vitalícia');
            
            if (license.days_remaining !== undefined && license.days_remaining !== null) {
                var daysText = license.days_remaining > 0 ? license.days_remaining + ' dias' : 'Expirado';
                if (license.days_remaining <= 7 && license.days_remaining > 0) {
                    daysText = '<span style="color:#d63638;font-weight:bold;">' + daysText + '</span>';
                }
                $('#puc-account-days').html(daysText);
            } else {
                $('#puc-days-remaining-row').hide();
            }

            // Stats
            $('#puc-stat-total-payments').text(stats.total_payments || 0);
            $('#puc-stat-confirmed-payments').text(stats.confirmed_payments || 0);
            $('#puc-stat-downloads').text(stats.downloads || 0);
            $('#puc-stat-updates').text(stats.updates || 0);

            // Plugins
            var pluginsHtml = '';
            if (plugins.length > 0) {
                plugins.forEach(function(plugin) {
                    pluginsHtml += '<div class="puc-plugin-item">' +
                        '<span class="dashicons dashicons-admin-plugins"></span>' +
                        '<div class="puc-plugin-info">' +
                        '<div class="puc-plugin-name">' + escapeHtml(plugin.name) + '</div>' +
                        '<div class="puc-plugin-version">v' + escapeHtml(plugin.version) + '</div>' +
                        '</div></div>';
                });
            } else {
                pluginsHtml = '<p>Nenhum plugin disponível</p>';
            }
            $('#puc-account-plugins').html(pluginsHtml);

            // Recent Activity
            var activityHtml = '';
            if (recentActivity.length > 0) {
                activityHtml = '<ul class="puc-activity-list">';
                recentActivity.forEach(function(activity) {
                    var iconClass = activity.type === 'download' ? 'download' : 'update';
                    var icon = activity.type === 'download' ? 'dashicons-download' : 'dashicons-update';
                    
                    activityHtml += '<li class="puc-activity-item">' +
                        '<div class="puc-activity-icon ' + iconClass + '">' +
                        '<span class="dashicons ' + icon + '"></span>' +
                        '</div>' +
                        '<div class="puc-activity-content">' +
                        '<div class="puc-activity-title">' + escapeHtml(activity.description || activity.plugin_name || 'Atividade') + '</div>' +
                        '<div class="puc-activity-meta">' + (activity.plugin_name || '') + '</div>' +
                        '</div>' +
                        '<div class="puc-activity-time">' + formatDate(activity.created_at) + '</div>' +
                        '</li>';
                });
                activityHtml += '</ul>';
            } else {
                activityHtml = '<p>Nenhuma atividade recente</p>';
            }
            $('#puc-account-activity').html(activityHtml);

            $('#puc-account-loading').hide();
            $('#puc-account-content').show();
        }

        // Load payments
        function loadPayments() {
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_get_payments',
                    nonce: pucAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        paymentsLoaded = true;
                        renderPayments(response.data);
                    } else {
                        $('#puc-payments-loading').hide();
                        $('#puc-payments-error').html('<p>' + (response.data || 'Erro ao carregar pagamentos') + '</p>').show();
                    }
                },
                error: function() {
                    $('#puc-payments-loading').hide();
                    $('#puc-payments-error').html('<p>Erro de conexão com o servidor</p>').show();
                }
            });
        }

        // Render payments
        function renderPayments(data) {
            var payments = data.payments || [];
            var summary = data.summary || {};

            // Summary cards
            var summaryHtml = '<div class="puc-summary-card">' +
                '<div class="puc-summary-value">' + (summary.total || 0) + '</div>' +
                '<div class="puc-summary-label">Total de Pagamentos</div>' +
                '</div>' +
                '<div class="puc-summary-card success">' +
                '<div class="puc-summary-value">' + (summary.confirmed || 0) + '</div>' +
                '<div class="puc-summary-label">Confirmados</div>' +
                '</div>' +
                '<div class="puc-summary-card warning">' +
                '<div class="puc-summary-value">' + (summary.pending || 0) + '</div>' +
                '<div class="puc-summary-label">Pendentes</div>' +
                '</div>' +
                '<div class="puc-summary-card">' +
                '<div class="puc-summary-value">R$ ' + formatMoney(summary.total_amount || 0) + '</div>' +
                '<div class="puc-summary-label">Total Pago</div>' +
                '</div>';
            $('#puc-payments-summary').html(summaryHtml);

            // Payments table
            if (payments.length > 0) {
                var tbodyHtml = '';
                payments.forEach(function(payment) {
                    var statusClass = payment.status;
                    tbodyHtml += '<tr>' +
                        '<td>#' + payment.id + '</td>' +
                        '<td>' + formatDate(payment.created_at) + '</td>' +
                        '<td>' + escapeHtml(payment.period_label || '-') + '</td>' +
                        '<td>' + escapeHtml(payment.method_label || payment.payment_method) + '</td>' +
                        '<td>R$ ' + formatMoney(payment.amount) + '</td>' +
                        '<td><span class="puc-payment-status ' + statusClass + '">' + escapeHtml(payment.status_label || payment.status) + '</span></td>' +
                        '<td>';
                    
                    if (payment.boleto_url) {
                        tbodyHtml += '<a href="' + payment.boleto_url + '" target="_blank" class="button button-small">Ver Boleto</a>';
                    }
                    
                    tbodyHtml += '</td></tr>';
                });
                $('#puc-payments-tbody').html(tbodyHtml);
                $('#puc-payments-table').show();
                $('#puc-payments-empty').hide();
            } else {
                $('#puc-payments-table').hide();
                $('#puc-payments-empty').show();
            }

            $('#puc-payments-loading').hide();
            $('#puc-payments-content').show();
        }

        // Load updates history
        function loadUpdatesHistory() {
            $.ajax({
                url: pucAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'puc_get_updates_history',
                    nonce: pucAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updatesLoaded = true;
                        renderUpdatesHistory(response.data);
                    } else {
                        $('#puc-updates-loading').hide();
                        $('#puc-updates-error').html('<p>' + (response.data || 'Erro ao carregar histórico') + '</p>').show();
                    }
                },
                error: function() {
                    $('#puc-updates-loading').hide();
                    $('#puc-updates-error').html('<p>Erro de conexão com o servidor</p>').show();
                }
            });
        }

        // Render updates history
        function renderUpdatesHistory(data) {
            var updates = data.updates || [];
            var stats = data.stats || {};

            // Stats
            var statsHtml = '<div class="puc-summary-card">' +
                '<div class="puc-summary-value">' + (stats.total || 0) + '</div>' +
                '<div class="puc-summary-label">Total de Atividades</div>' +
                '</div>' +
                '<div class="puc-summary-card success">' +
                '<div class="puc-summary-value">' + (stats.downloads || 0) + '</div>' +
                '<div class="puc-summary-label">Downloads</div>' +
                '</div>' +
                '<div class="puc-summary-card">' +
                '<div class="puc-summary-value">' + (stats.updates || 0) + '</div>' +
                '<div class="puc-summary-label">Atualizações</div>' +
                '</div>';
            $('#puc-updates-stats').html(statsHtml);

            // Store updates for filtering
            window.pucUpdatesData = updates;

            // Render table
            renderUpdatesTable(updates);

            $('#puc-updates-loading').hide();
            $('#puc-updates-content').show();
        }

        // Render updates table
        function renderUpdatesTable(updates) {
            if (updates.length > 0) {
                var tbodyHtml = '';
                updates.forEach(function(update) {
                    var typeClass = update.type;
                    var typeLabel = update.type === 'download' ? 'Download' : 'Atualização';
                    
                    var versionInfo = '';
                    if (update.type === 'update' && update.from_version && update.to_version) {
                        versionInfo = '<span class="puc-version-info">' + 
                            escapeHtml(update.from_version) + 
                            '<span class="puc-version-arrow">→</span>' + 
                            escapeHtml(update.to_version) + '</span>';
                    } else if (update.version) {
                        versionInfo = 'v' + escapeHtml(update.version);
                    }
                    
                    tbodyHtml += '<tr data-type="' + update.type + '">' +
                        '<td>' + formatDate(update.created_at) + '</td>' +
                        '<td><span class="puc-update-type ' + typeClass + '">' + typeLabel + '</span></td>' +
                        '<td>' + escapeHtml(update.plugin_name || update.plugin_slug || '-') + '</td>' +
                        '<td>' + versionInfo + '</td>' +
                        '<td>' + escapeHtml(update.description || '-') + '</td>' +
                        '</tr>';
                });
                $('#puc-updates-tbody').html(tbodyHtml);
                $('#puc-updates-table').show();
                $('#puc-updates-empty').hide();
            } else {
                $('#puc-updates-table').hide();
                $('#puc-updates-empty').show();
            }
        }

        // Filter updates by type
        $('#puc-updates-type-filter').on('change', function() {
            var filterType = $(this).val();
            
            if (!filterType) {
                renderUpdatesTable(window.pucUpdatesData || []);
            } else {
                var filtered = (window.pucUpdatesData || []).filter(function(update) {
                    return update.type === filterType;
                });
                renderUpdatesTable(filtered);
            }
        });

        // Helper functions
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            var date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
        }

        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    });

})(jQuery);

