<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizações Automáticas para WordPress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .hero {
            padding: 80px 0;
            text-align: center;
            color: white;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .pricing-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }
        .pricing-card:hover {
            transform: translateY(-5px);
        }
        .pricing-card.featured {
            border: 3px solid var(--primary);
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
            font-size: 0.85rem;
            font-weight: 600;
        }
        .price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        .price small {
            font-size: 1rem;
            color: #666;
            font-weight: 400;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .feature-list i {
            color: #10b981;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            padding: 12px 24px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        .info-box {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 2rem;
            color: white;
            margin-top: 3rem;
        }
        .info-box h5 {
            margin-bottom: 1rem;
        }
        .footer {
            text-align: center;
            padding: 2rem;
            color: rgba(255,255,255,0.7);
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="container">
            <h1><i class="bi bi-cloud-download"></i> Atualizações Automáticas</h1>
            <p>Atualizações automáticas para seus plugins WordPress premium. Receba sempre as últimas versões diretamente no painel do WordPress.</p>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4 justify-content-center">
            <!-- Mensal -->
            <div class="col-md-6 col-lg-3">
                <div class="pricing-card">
                    <h4>Mensal</h4>
                    <div class="price">R$ <?= number_format($prices['monthly'], 2, ',', '.') ?> <small>/mês</small></div>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                        <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                        <li><i class="bi bi-check-circle-fill"></i> Suporte por email</li>
                        <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                    </ul>
                </div>
            </div>
            
            <!-- Trimestral -->
            <div class="col-md-6 col-lg-3">
                <div class="pricing-card">
                    <h4>Trimestral</h4>
                    <div class="price">R$ <?= number_format($prices['quarterly'], 2, ',', '.') ?> <small>/3 meses</small></div>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                        <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                        <li><i class="bi bi-check-circle-fill"></i> Suporte por email</li>
                        <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                    </ul>
                </div>
            </div>
            
            <!-- Semestral -->
            <div class="col-md-6 col-lg-3">
                <div class="pricing-card featured">
                    <h4>Semestral</h4>
                    <div class="price">R$ <?= number_format($prices['semiannual'], 2, ',', '.') ?> <small>/6 meses</small></div>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                        <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                        <li><i class="bi bi-check-circle-fill"></i> Suporte prioritário</li>
                        <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                    </ul>
                </div>
            </div>
            
            <!-- Anual -->
            <div class="col-md-6 col-lg-3">
                <div class="pricing-card">
                    <h4>Anual</h4>
                    <div class="price">R$ <?= number_format($prices['yearly'], 2, ',', '.') ?> <small>/ano</small></div>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill"></i> Todos os plugins</li>
                        <li><i class="bi bi-check-circle-fill"></i> Atualizações automáticas</li>
                        <li><i class="bi bi-check-circle-fill"></i> Suporte prioritário</li>
                        <li><i class="bi bi-check-circle-fill"></i> 1 site</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="info-box">
            <h5><i class="bi bi-info-circle"></i> Como funciona?</h5>
            <p class="mb-0">
                A assinatura é feita diretamente pelo plugin instalado no seu WordPress. 
                Após instalar o plugin cliente, você poderá escolher o período de assinatura e realizar o pagamento 
                via PIX, Boleto ou Cartão de Crédito. Após a confirmação do pagamento, sua licença será ativada 
                automaticamente e você receberá as atualizações de todos os plugins premium.
            </p>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?= date('Y') ?> Todos os direitos reservados.</p>
        <a href="<?= url('/login') ?>" class="text-white">Área Administrativa</a>
    </div>
</body>
</html>
