# Premium Updates Server

Sistema de gerenciamento de licenças e atualizações de plugins WordPress premium.

## 🚀 Requisitos

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Extensões PHP: PDO, pdo_mysql, json, curl, zip

## 📦 Instalação

### 1. Clone ou faça download do projeto

```bash
git clone https://github.com/seu-usuario/updates-wordpress-auto.git
cd updates-wordpress-auto/server
```

### 2. Configure o banco de dados

```bash
# Crie o banco de dados
mysql -u root -p -e "CREATE DATABASE premium_updates CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importe o schema
mysql -u root -p premium_updates < database/schema.sql
```

### 3. Configure as variáveis de ambiente

```bash
cp .env.example .env
# Edite o arquivo .env com suas configurações
```

### 4. Configure o servidor web

**Apache (recomendado):**
Aponte o DocumentRoot para a pasta `public/`

**Nginx:**
```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /caminho/para/server/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Configure permissões

```bash
chmod -R 755 storage/
chmod -R 755 public/
```

### 6. Acesse o sistema

Abra o navegador em `http://seu-dominio.com`

**Credenciais padrão:**
- Email: admin@admin.com
- Senha: admin123

⚠️ **IMPORTANTE:** Altere a senha após o primeiro acesso!

## 🔧 Estrutura do Projeto

```
server/
├── app/
│   ├── Controllers/     # Controladores
│   │   ├── Admin/       # Controladores do painel admin
│   │   └── Api/         # Controladores da API
│   ├── Core/            # Classes principais (Router, Controller, Database)
│   ├── Middleware/      # Middlewares
│   ├── Models/          # Modelos de dados
│   ├── Services/        # Serviços (Asaas, etc)
│   └── helpers.php      # Funções auxiliares
├── config/
│   └── app.php          # Configurações do sistema
├── database/
│   └── schema.sql       # Schema do banco de dados
├── public/
│   ├── index.php        # Ponto de entrada
│   └── .htaccess        # Regras do Apache
├── resources/
│   └── views/           # Templates HTML
├── routes/
│   ├── web.php          # Rotas web
│   └── api.php          # Rotas da API
├── storage/
│   ├── plugins/         # Arquivos ZIP dos plugins
│   └── logs/            # Logs do sistema
└── .env.example         # Exemplo de configuração
```

## 📡 API Endpoints

### Validar Licença
```
POST /api/v1/validate-license
Body: { "license_key": "XXXX-XXXX-XXXX-XXXX", "site_url": "https://site.com" }
```

### Verificar Atualizações
```
POST /api/v1/check-updates
Body: { "license_key": "...", "plugins": { "plugin-slug": "1.0.0" } }
```

### Download de Plugin
```
POST /api/v1/download/{slug}
Body: { "license_key": "..." }
```

## 💳 Integração Asaas

1. Crie uma conta em [asaas.com](https://www.asaas.com)
2. Obtenha sua API Key em Integrações > API
3. Configure a URL do webhook: `https://seu-dominio.com/api/v1/webhook/asaas`
4. Adicione as credenciais no arquivo `.env`

## 🔒 Segurança

- Sempre use HTTPS em produção
- Altere as credenciais padrão imediatamente
- Mantenha o PHP e MySQL atualizados
- Configure backups automáticos do banco de dados

## 📄 Licença

Este projeto é proprietário. Uso permitido apenas com autorização.
