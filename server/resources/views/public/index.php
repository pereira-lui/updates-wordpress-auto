<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de atualizações automáticas para plugins WordPress premium. Gerencie suas licenças e receba atualizações diretamente no painel do WordPress.">
    <meta name="robots" content="index, follow">
    <title>Atualizações Automáticas para WordPress | Luia Systems</title>
    <link rel="canonical" href="https://www.luiasystems.com/updates-wordpress-auto">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #0ea5e9;
            --success: #10b981;
            --dark: #1e293b;
            --gray: #64748b;
            --light: #f8fafc;
        }
        
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: var(--light);
            color: var(--dark);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        
        .hero p.lead {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            max-width: 540px;
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }
        
        .hero-image {
            position: relative;
        }
        
        .hero-image img {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .hero-mockup {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .mockup-header {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .mockup-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .mockup-dot.red { background: #ff5f56; }
        .mockup-dot.yellow { background: #ffbd2e; }
        .mockup-dot.green { background: #27ca40; }
        
        .mockup-content {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            min-height: 250px;
        }
        
        .mockup-plugin {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .mockup-plugin-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .mockup-plugin-info {
            flex: 1;
        }
        
        .mockup-plugin-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
        }
        
        .mockup-plugin-version {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        .mockup-update-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Features Section */
        .features {
            padding: 100px 0;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto 3rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--dark);
        }
        
        .feature-card p {
            color: var(--gray);
            margin: 0;
            line-height: 1.6;
        }
        
        /* How it works */
        .how-it-works {
            padding: 100px 0;
            background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        }
        
        .step-card {
            text-align: center;
            padding: 2rem;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }
        
        .step-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .step-card p {
            color: var(--gray);
        }
        
        .step-connector {
            position: relative;
        }
        
        .step-connector::after {
            content: '';
            position: absolute;
            top: 30px;
            left: 60%;
            width: 80%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }
        
        /* Pricing Section */
        .pricing {
            padding: 100px 0;
            background: white;
        }
        
        .pricing-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .pricing-card.featured {
            border-color: var(--primary);
            position: relative;
        }
        
        .pricing-card.featured::before {
            content: 'Mais Popular';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .pricing-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .pricing-period {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin: 1.5rem 0;
        }
        
        .pricing-price small {
            font-size: 1rem;
            color: var(--gray);
            font-weight: 400;
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0 2rem;
            text-align: left;
        }
        
        .pricing-features li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
        }
        
        .pricing-features i {
            color: var(--success);
        }
        
        /* CTA Section */
        .cta {
            padding: 100px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }
        
        .cta p {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Buttons */
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            padding: 14px 28px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-outline-light {
            border-width: 2px;
            padding: 14px 28px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .btn-lg {
            padding: 16px 32px;
            font-size: 1.1rem;
        }
        
        /* FAQ */
        .faq {
            padding: 100px 0;
            background: var(--light);
        }
        
        .accordion-item {
            border: none;
            margin-bottom: 1rem;
            border-radius: 12px !important;
            overflow: hidden;
        }
        
        .accordion-button {
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            background: white;
        }
        
        .accordion-button:not(.collapsed) {
            background: white;
            color: var(--primary);
            box-shadow: none;
        }
        
        .accordion-button:focus {
            box-shadow: none;
            border-color: transparent;
        }
        
        .accordion-body {
            padding: 0 1.5rem 1.5rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            padding: 60px 0 30px;
            color: rgba(255,255,255,0.7);
        }
        
        .footer h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }
        
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            margin-top: 3rem;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-mockup {
                margin-top: 3rem;
            }
            
            .step-connector::after {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.75rem;
            }
            
            .pricing-price {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <span class="hero-badge">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Sistema Automatizado
                    </span>
                    <h1>Atualizações Automáticas para WordPress</h1>
                    <p class="lead">
                        Receba atualizações de plugins premium diretamente no painel do seu WordPress. 
                        Sem downloads manuais, sem complicação. Tudo automático.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#como-funciona" class="btn btn-light btn-lg">
                            <i class="bi bi-play-circle me-2"></i> Como Funciona
                        </a>
                        <a href="#precos" class="btn btn-outline-light btn-lg">
                            Ver Preços
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image">
                    <div class="hero-mockup">
                        <div class="mockup-header">
                            <span class="mockup-dot red"></span>
                            <span class="mockup-dot yellow"></span>
                            <span class="mockup-dot green"></span>
                        </div>
                        <div class="mockup-content">
                            <div class="mockup-plugin">
                                <div class="mockup-plugin-icon">
                                    <i class="bi bi-puzzle"></i>
                                </div>
                                <div class="mockup-plugin-info">
                                    <div class="mockup-plugin-name">Premium Plugin Pro</div>
                                    <div class="mockup-plugin-version">v2.5.0 → v2.6.0 disponível</div>
                                </div>
                                <button class="mockup-update-btn">
                                    <i class="bi bi-arrow-repeat me-1"></i> Atualizar
                                </button>
                            </div>
                            <div class="mockup-plugin">
                                <div class="mockup-plugin-icon">
                                    <i class="bi bi-gear"></i>
                                </div>
                                <div class="mockup-plugin-info">
                                    <div class="mockup-plugin-name">Advanced Tools</div>
                                    <div class="mockup-plugin-version">v1.8.3 - Atualizado ✓</div>
                                </div>
                                <span class="badge bg-success">Ativo</span>
                            </div>
                            <div class="mockup-plugin">
                                <div class="mockup-plugin-icon">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <div class="mockup-plugin-info">
                                    <div class="mockup-plugin-name">Performance Boost</div>
                                    <div class="mockup-plugin-version">v3.1.2 - Atualizado ✓</div>
                                </div>
                                <span class="badge bg-success">Ativo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="recursos">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Por que usar nosso sistema?</h2>
                <p class="section-subtitle">
                    Simplifique a gestão dos seus plugins premium com atualizações automáticas e seguras
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <h3>Atualizações Automáticas</h3>
                        <p>Receba notificações e atualize seus plugins diretamente pelo painel do WordPress, igual aos plugins oficiais.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3>100% Seguro</h3>
                        <p>Downloads criptografados e licenças validadas. Apenas sites autorizados recebem as atualizações.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <h3>Pagamento Fácil</h3>
                        <p>Pague via PIX ou Boleto diretamente no seu WordPress. Licença ativada automaticamente após confirmação.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h3>Relatórios Completos</h3>
                        <p>Acompanhe seu histórico de pagamentos, atualizações realizadas e status da sua assinatura.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h3>Nota Fiscal</h3>
                        <p>Opção de receber nota fiscal em cada pagamento. Ideal para empresas que precisam de comprovação.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h3>Suporte Dedicado</h3>
                        <p>Suporte técnico para ajudar com instalação, configuração e quaisquer dúvidas sobre os plugins.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="how-it-works" id="como-funciona">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Como Funciona?</h2>
                <p class="section-subtitle">
                    Em apenas 3 passos simples você começa a receber atualizações automáticas
                </p>
            </div>
            
            <div class="row">
                <div class="col-md-4 step-connector">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3>Instale o Plugin Cliente</h3>
                        <p>Baixe e instale o plugin "Premium Updates Client" no seu WordPress. É gratuito e leva menos de 1 minuto.</p>
                    </div>
                </div>
                
                <div class="col-md-4 step-connector">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3>Escolha seu Plano</h3>
                        <p>Diretamente no plugin, escolha o período de assinatura e faça o pagamento via PIX ou Boleto.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3>Receba Atualizações</h3>
                        <p>Pronto! Sua licença é ativada automaticamente e você já pode atualizar os plugins pelo WordPress.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="precos">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Planos e Preços</h2>
                <p class="section-subtitle">
                    Escolha o período que melhor se adapta às suas necessidades
                </p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-3">
                    <div class="pricing-card">
                        <h3>Mensal</h3>
                        <p class="pricing-period">Pague mês a mês</p>
                        <div class="pricing-price">
                            R$ <?= number_format($prices['monthly'], 2, ',', '.') ?>
                            <small>/mês</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                            <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                            <li><i class="bi bi-check-circle-fill"></i> Suporte por email</li>
                            <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="pricing-card">
                        <h3>Trimestral</h3>
                        <p class="pricing-period">3 meses</p>
                        <div class="pricing-price">
                            R$ <?= number_format($prices['quarterly'], 2, ',', '.') ?>
                            <small>/trimestre</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                            <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                            <li><i class="bi bi-check-circle-fill"></i> Suporte por email</li>
                            <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="pricing-card featured">
                        <h3>Semestral</h3>
                        <p class="pricing-period">6 meses</p>
                        <div class="pricing-price">
                            R$ <?= number_format($prices['semiannual'], 2, ',', '.') ?>
                            <small>/semestre</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                            <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                            <li><i class="bi bi-check-circle-fill"></i> Suporte prioritário</li>
                            <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="pricing-card">
                        <h3>Anual</h3>
                        <p class="pricing-period">12 meses</p>
                        <div class="pricing-price">
                            R$ <?= number_format($prices['yearly'], 2, ',', '.') ?>
                            <small>/ano</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                            <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                            <li><i class="bi bi-check-circle-fill"></i> Suporte prioritário</li>
                            <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <p class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    A contratação é feita diretamente pelo plugin instalado no seu WordPress
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Perguntas Frequentes</h2>
                <p class="section-subtitle">
                    Tire suas dúvidas sobre o sistema de atualizações
                </p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Como instalo o plugin cliente?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Baixe o arquivo ZIP do plugin cliente na seção de releases. No seu WordPress, vá em 
                                    <strong>Plugins > Adicionar Novo > Enviar Plugin</strong>, selecione o arquivo ZIP e clique em 
                                    "Instalar Agora". Após a instalação, ative o plugin e configure em <strong>Configurações > Premium Updates</strong>.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Quais formas de pagamento são aceitas?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Aceitamos pagamento via <strong>PIX</strong> (aprovação imediata) e <strong>Boleto Bancário</strong> 
                                    (aprovação em até 3 dias úteis). O pagamento é feito diretamente pelo plugin instalado no seu WordPress, 
                                    de forma segura através do gateway Asaas.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    A licença é ativada automaticamente?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sim! Após a confirmação do pagamento, sua licença é ativada automaticamente e vinculada ao seu site. 
                                    Para pagamentos via PIX, a ativação é praticamente instantânea. Para boletos, a ativação ocorre 
                                    assim que o banco confirma o pagamento.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Posso usar em mais de um site?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Cada licença é válida para um site. Se você precisa usar os plugins em múltiplos sites, 
                                    será necessário contratar uma licença para cada um. A licença fica vinculada à URL do site 
                                    onde foi ativada.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    O que acontece quando a licença expira?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Quando sua licença expira, você deixa de receber atualizações de novos plugins. Os plugins já 
                                    instalados continuam funcionando normalmente, mas não receberão novas versões. Você receberá 
                                    avisos no painel do WordPress antes da expiração para renovar sua assinatura.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    Posso solicitar nota fiscal?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sim! No momento do pagamento, você pode marcar a opção "Desejo receber nota fiscal". 
                                    A nota será emitida após a confirmação do pagamento e enviada para o e-mail cadastrado. 
                                    É necessário informar o CPF ou CNPJ para emissão da nota.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Pronto para começar?</h2>
            <p>
                Instale o plugin cliente e comece a receber atualizações automáticas dos seus plugins premium hoje mesmo.
            </p>
            <a href="https://github.com/pereira-lui/updates-wordpress-auto/releases" target="_blank" class="btn btn-light btn-lg">
                <i class="bi bi-download me-2"></i> Baixar Plugin Cliente
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5><i class="bi bi-cloud-download me-2"></i> Atualizações Automáticas</h5>
                    <p>
                        Sistema profissional de distribuição de atualizações para plugins WordPress premium.
                    </p>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5>Links</h5>
                    <ul class="footer-links">
                        <li><a href="#recursos">Recursos</a></li>
                        <li><a href="#como-funciona">Como Funciona</a></li>
                        <li><a href="#precos">Preços</a></li>
                        <li><a href="#faq">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5>Suporte</h5>
                    <ul class="footer-links">
                        <li><a href="mailto:suporte@luiasystems.com">suporte@luiasystems.com</a></li>
                        <li><a href="https://github.com/pereira-lui/updates-wordpress-auto" target="_blank">GitHub</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-4">
                    <h5>Desenvolvido por</h5>
                    <p>
                        <a href="https://www.luiasystems.com" target="_blank">
                            <strong>Luia Systems</strong>
                        </a>
                        <br>
                        Soluções em tecnologia e desenvolvimento web
                    </p>
                </div>
            </div>
            
            <div class="footer-bottom text-center">
                <p class="mb-0">
                    &copy; <?= date('Y') ?> Luia Systems. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
