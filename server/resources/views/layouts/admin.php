<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin' ?> - Premium Updates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
        }
        
        body {
            background: #f3f4f6;
            min-height: 100vh;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            padding: 1rem;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-brand {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            padding: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar-nav .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }
        
        .navbar-top {
            background: white;
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 0.75rem;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
        }
        
        .stat-card {
            padding: 1.5rem;
        }
        
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }
        
        .stat-card .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .table th {
            font-weight: 600;
            color: #374151;
            border-bottom-width: 1px;
        }
        
        .badge-active { background: #10b981; }
        .badge-pending { background: #f59e0b; }
        .badge-expired { background: #ef4444; }
        .badge-cancelled { background: #6b7280; }
        
        .badge-paid { background: #6366f1; }
        .badge-lifetime { background: #8b5cf6; }
        .badge-friend { background: #ec4899; }
        .badge-trial { background: #06b6d4; }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-cloud-arrow-up"></i> Premium Updates
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/admin') && !str_contains($_SERVER['REQUEST_URI'], '/admin/') ? 'active' : '' ?>" href="<?= url('/admin') ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/plugins') ? 'active' : '' ?>" href="<?= url('/admin/plugins') ?>">
                        <i class="bi bi-puzzle"></i> Plugins
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/licenses') ? 'active' : '' ?>" href="<?= url('/admin/licenses') ?>">
                        <i class="bi bi-key"></i> Licenças
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/payments') ? 'active' : '' ?>" href="<?= url('/admin/payments') ?>">
                        <i class="bi bi-credit-card"></i> Pagamentos
                    </a>
                </li>
                
                <li class="nav-item mt-4">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/settings') ? 'active' : '' ?>" href="<?= url('/admin/settings') ?>">
                        <i class="bi bi-gear"></i> Configurações
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/logout') ?>">
                        <i class="bi bi-box-arrow-left"></i> Sair
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="navbar-top">
            <div>
                <h4 class="mb-0"><?= $title ?? 'Dashboard' ?></h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted">
                    <i class="bi bi-person-circle"></i> <?= auth()->name ?? 'Admin' ?>
                </span>
            </div>
        </div>
        
        <?php if ($flash = flash()): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?= $content ?>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php if (isset($sections['scripts'])): ?>
        <?= $sections['scripts'] ?>
    <?php endif; ?>
</body>
</html>
