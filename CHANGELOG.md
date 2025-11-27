# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.1.0] - 2025-11-27

### Adicionado
- **Integração com Asaas para pagamentos**
  - Suporte a PIX, Boleto e Cartão de Crédito
  - Cobranças únicas e recorrentes (assinaturas)
  - Webhooks para ativação automática de licenças
  - Modo sandbox para testes

- **Sistema de Planos**
  - Gerenciamento de planos de preços
  - Suporte a planos mensais, anuais e vitalícios
  - Configuração de limite de sites por plano
  - Lista de recursos personalizável

- **Página de Checkout Pública**
  - Shortcode [pus_pricing] para tabela de preços
  - Shortcode [pus_checkout] para formulário de compra
  - Design moderno e responsivo
  - Validação de CPF/CNPJ

- **Recursos Adicionais**
  - E-mail automático com dados da licença após pagamento
  - Logs de webhooks para debug
  - Status de pagamento nas licenças

## [1.0.0] - 2025-11-27

### Adicionado
- **Plugin Servidor (Premium Updates Server)**
  - Sistema de gerenciamento de plugins premium
  - Cadastro de plugins com versão, changelog, ícone e banner
  - Sistema de licenças com chaves únicas por cliente
  - Controle de expiração de licenças
  - API REST para comunicação com clientes
  - Logs de todas as atualizações realizadas
  - Painel administrativo completo

- **Plugin Cliente (Premium Updates Client)**
  - Integração com sistema nativo de atualizações do WordPress
  - Verificação automática de atualizações (2x ao dia)
  - Configuração de servidor e licença
  - Teste de conexão com servidor
  - Seleção de plugins gerenciados
  - Sincronização com lista de plugins do servidor

### Segurança
- Validação de licença em todas as requisições
- URLs de download protegidas
- Logs de IP e site em cada atualização
