# Premium Updates - Sistema de Atualização de Plugins WordPress

Sistema completo para distribuição de atualizações automáticas de plugins WordPress premium para múltiplos sites de clientes.

## 📋 Visão Geral

Este projeto consiste em dois plugins WordPress:

1. **Premium Updates Server** - Instalado no seu servidor central
2. **Premium Updates Client** - Instalado nos sites dos clientes

O servidor armazena os plugins premium e suas versões, enquanto os clientes verificam periodicamente por atualizações e as instalam automaticamente através do sistema nativo do WordPress.

## 🚀 Instalação

### No Servidor (seu site principal)

1. Faça upload da pasta `server-plugin` para `/wp-content/plugins/`
2. Renomeie para `premium-updates-server`
3. Ative o plugin no WordPress
4. Acesse **Premium Updates** no menu do admin

### Nos Sites dos Clientes

1. Faça upload da pasta `client-plugin` para `/wp-content/plugins/`
2. Renomeie para `premium-updates-client`
3. Ative o plugin no WordPress
4. Acesse **Configurações → Premium Updates**

## ⚙️ Configuração

### Configurando o Servidor

#### 1. Adicionar Plugins Premium

1. Vá em **Premium Updates → Plugins**
2. Clique em **Adicionar Novo**
3. Preencha as informações:
   - **Nome do Plugin**: Nome de exibição
   - **Slug**: Nome da pasta do plugin (ex: `meu-plugin-premium`)
   - **Versão**: Versão atual (ex: `1.0.0`)
   - **URL do Pacote ZIP**: Link direto para download do arquivo .zip

> **Dica**: Você pode hospedar os arquivos ZIP em qualquer servidor, como Amazon S3, Google Cloud Storage, ou até mesmo no próprio servidor usando a biblioteca de mídia do WordPress.

#### 2. Criar Licenças para Clientes

1. Vá em **Premium Updates → Licenças**
2. Clique em **Adicionar Nova**
3. Preencha:
   - **Nome do Cliente**: Para identificação
   - **E-mail**: Opcional
   - **URL do Site**: URL completa do site do cliente
   - **Data de Expiração**: Deixe em branco para licença vitalícia

Uma chave de licença será gerada automaticamente.

### Configurando os Clientes

1. Acesse **Configurações → Premium Updates**
2. Configure:
   - **URL do Servidor**: URL do seu site com o plugin servidor
   - **Chave de Licença**: Chave gerada no servidor
3. Clique em **Testar Conexão** para verificar
4. Marque os plugins que devem receber atualizações automáticas
5. Salve as configurações

## 🔄 Como Funciona

1. O cliente verifica o servidor 2x ao dia por atualizações
2. Quando uma nova versão é encontrada, aparece na tela de atualizações do WordPress
3. O administrador pode atualizar normalmente ou configurar atualizações automáticas
4. Cada atualização é registrada no log do servidor

## 📡 API REST

O servidor expõe os seguintes endpoints:

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/wp-json/premium-updates/v1/check-updates` | POST | Verifica atualizações |
| `/wp-json/premium-updates/v1/plugin-info/{slug}` | POST | Informações do plugin |
| `/wp-json/premium-updates/v1/download/{slug}` | POST | URL de download |
| `/wp-json/premium-updates/v1/validate-license` | POST | Valida licença |
| `/wp-json/premium-updates/v1/plugins` | POST | Lista plugins |

Todos os endpoints requerem `license_key` e `site_url` no body.

## 🔐 Segurança

- Todas as requisições são validadas contra a licença
- URLs de download são protegidas
- Licenças podem ser desativadas ou ter data de expiração
- Logs completos de todas as atualizações

## 📁 Estrutura do Projeto

```
updates-wordpress-auto/
├── server-plugin/
│   ├── premium-updates-server.php
│   ├── includes/
│   │   ├── class-pus-database.php
│   │   ├── class-pus-api.php
│   │   ├── class-pus-admin.php
│   │   └── class-pus-plugin-manager.php
│   ├── templates/
│   │   ├── admin-plugins.php
│   │   ├── admin-licenses.php
│   │   ├── admin-logs.php
│   │   └── admin-settings.php
│   └── assets/
│       ├── css/admin.css
│       └── js/admin.js
│
├── client-plugin/
│   ├── premium-updates-client.php
│   ├── templates/
│   │   └── settings.php
│   └── assets/
│       ├── css/admin.css
│       └── js/admin.js
│
└── README.md
```

## 🔧 Fluxo de Atualização de Plugin

1. Atualize o arquivo ZIP do plugin no servidor
2. Vá em **Premium Updates → Plugins**
3. Edite o plugin e atualize o número da versão
4. Os clientes receberão a notificação de atualização automaticamente

## 📝 Requisitos

- WordPress 5.0 ou superior
- PHP 7.4 ou superior
- SSL habilitado (recomendado)

## 🤝 Suporte

Para dúvidas ou problemas, abra uma issue no repositório:
https://github.com/pereira-lui/updates-wordpress-auto

## 📄 Licença

GPL v2 ou posterior

---

Desenvolvido por [Lui Pereira](https://github.com/pereira-lui)
