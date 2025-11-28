<?php
/**
 * Script de configuração inicial
 * APAGUE ESTE ARQUIVO APÓS USAR!
 */

define('PUS_SYSTEM', true);
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Carrega .env
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value, '"\''));
        }
    }
}

$config = require CONFIG_PATH . '/app.php';

echo "<h1>Setup Premium Updates</h1>";
echo "<pre>";

// Testa conexão
echo "1. Testando conexão com banco de dados...\n";
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database']
    );
    
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "   ✅ Conexão OK!\n";
    echo "   Host: " . $config['database']['host'] . "\n";
    echo "   Banco: " . $config['database']['database'] . "\n";
    echo "   Usuário: " . $config['database']['username'] . "\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    die("</pre>");
}

// Verifica se tabela users existe
echo "2. Verificando tabela users...\n";
$tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
if (empty($tables)) {
    echo "   ❌ Tabela 'users' não existe!\n";
    echo "   Execute o schema.sql no phpMyAdmin.\n";
    die("</pre>");
}
echo "   ✅ Tabela existe!\n\n";

// Lista usuários existentes
echo "3. Usuários existentes:\n";
$users = $pdo->query("SELECT id, username, email, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
if (empty($users)) {
    echo "   Nenhum usuário encontrado.\n";
} else {
    foreach ($users as $user) {
        echo "   - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}\n";
    }
}
echo "\n";

// Cria/Atualiza usuário admin
echo "4. Criando/Atualizando usuário admin...\n";
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' OR email = 'admin@admin.com'");
$stmt->execute();
$existing = $stmt->fetch();

if ($existing) {
    // Atualiza
    $stmt = $pdo->prepare("UPDATE users SET password = ?, email = 'admin@admin.com', username = 'admin' WHERE id = ?");
    $stmt->execute([$hash, $existing['id']]);
    echo "   ✅ Usuário admin atualizado!\n";
} else {
    // Cria
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['admin', 'admin@admin.com', $hash, 'Administrador', 'admin']);
    echo "   ✅ Usuário admin criado!\n";
}

echo "\n";
echo "============================================\n";
echo "CREDENCIAIS DE ACESSO:\n";
echo "Email: admin@admin.com\n";
echo "Senha: admin123\n";
echo "============================================\n";
echo "\n";
echo "⚠️  IMPORTANTE: Apague este arquivo (setup.php) após o login!\n";
echo "</pre>";

echo "<p><a href='" . ($config['url'] ?? '') . "/login'>👉 Clique aqui para fazer login</a></p>";
