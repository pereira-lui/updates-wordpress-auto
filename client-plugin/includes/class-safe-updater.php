<?php
/**
 * Safe Updater - Sistema de atualização segura com rollback automático
 * 
 * Este módulo garante que atualizações de plugins não quebrem o site:
 * 1. Faz backup do plugin antes de atualizar
 * 2. Executa a atualização
 * 3. Verifica a saúde do site após a atualização
 * 4. Reverte automaticamente se detectar erros
 *
 * @package Premium_Updates_Client
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUC_Safe_Updater {

    /**
     * Diretório de backups
     */
    private $backup_dir;

    /**
     * Timeout para health check (segundos)
     */
    private $health_check_timeout = 10;

    /**
     * Número de tentativas de health check
     */
    private $health_check_retries = 3;

    /**
     * Instância singleton
     */
    private static $instance = null;

    /**
     * Obtém instância singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor
     */
    private function __construct() {
        $this->backup_dir = WP_CONTENT_DIR . '/puc-backups';
        
        $this->init_hooks();
        $this->ensure_backup_dir();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Hook ANTES da atualização - criar backup
        add_filter('upgrader_pre_install', array($this, 'pre_install_backup'), 10, 2);
        
        // Hook APÓS a atualização - verificar saúde
        add_filter('upgrader_post_install', array($this, 'post_install_health_check'), 10, 3);
        
        // Hook para quando o processo de atualização terminar
        add_action('upgrader_process_complete', array($this, 'cleanup_old_backups'), 20, 2);
        
        // Endpoint para health check (usado internamente)
        add_action('wp_ajax_puc_health_check', array($this, 'ajax_health_check'));
        add_action('wp_ajax_nopriv_puc_health_check', array($this, 'ajax_health_check'));
        
        // REST API endpoint para health check externo
        add_action('rest_api_init', array($this, 'register_health_check_endpoint'));
        
        // AJAX para rollback manual
        add_action('wp_ajax_puc_manual_rollback', array($this, 'ajax_manual_rollback'));
        add_action('wp_ajax_puc_get_backups', array($this, 'ajax_get_backups'));
        add_action('wp_ajax_puc_delete_backup', array($this, 'ajax_delete_backup'));
    }

    /**
     * Garante que o diretório de backup existe
     */
    private function ensure_backup_dir() {
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            
            // Protege o diretório
            $htaccess = $this->backup_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Deny from all\n");
            }
            
            $index = $this->backup_dir . '/index.php';
            if (!file_exists($index)) {
                file_put_contents($index, "<?php // Silence is golden\n");
            }
        }
    }

    /**
     * Hook PRE-INSTALL: Cria backup antes de atualizar
     */
    public function pre_install_backup($response, $hook_extra) {
        // Verifica se é uma atualização de plugin
        if (!isset($hook_extra['plugin'])) {
            return $response;
        }

        $plugin_file = $hook_extra['plugin'];
        $managed_plugins = get_option('puc_managed_plugins', array());

        // Só faz backup de plugins gerenciados
        if (!in_array($plugin_file, $managed_plugins)) {
            return $response;
        }

        // Extrai o slug do plugin
        $parts = explode('/', $plugin_file);
        $plugin_slug = $parts[0];
        
        // Obtém versão atual
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
        $current_version = $plugin_data['Version'] ?? 'unknown';
        
        // Obtém versão para a qual está atualizando
        $to_version = $this->get_updating_version($plugin_slug);

        // Cria backup
        $backup_result = $this->create_backup($plugin_slug, $current_version);

        if (is_wp_error($backup_result)) {
            // Log do erro mas não bloqueia a atualização
            $this->log('Erro ao criar backup: ' . $backup_result->get_error_message());
        } else {
            $this->log("Backup criado para {$plugin_slug} v{$current_version}");
            
            // Armazena informação do backup para uso posterior
            set_transient('puc_current_backup_' . $plugin_slug, array(
                'path' => $backup_result,
                'version' => $current_version,
                'to_version' => $to_version,
                'plugin_file' => $plugin_file,
                'created_at' => time()
            ), HOUR_IN_SECONDS);
            
            // Notifica servidor que a atualização iniciou
            $this->report_update_started($plugin_slug, $current_version, $to_version);
        }

        return $response;
    }
    
    /**
     * Obtém a versão para a qual está atualizando
     */
    private function get_updating_version($plugin_slug) {
        $update_plugins = get_site_transient('update_plugins');
        
        if (!$update_plugins) {
            return 'unknown';
        }
        
        foreach ($update_plugins->response as $file => $plugin) {
            if (strpos($file, $plugin_slug . '/') === 0) {
                return $plugin->new_version ?? 'unknown';
            }
        }
        
        return 'unknown';
    }

    /**
     * Hook POST-INSTALL: Verifica saúde após atualização
     */
    public function post_install_health_check($response, $hook_extra, $result) {
        // Verifica se é uma atualização de plugin
        if (!isset($hook_extra['plugin'])) {
            return $response;
        }

        $plugin_file = $hook_extra['plugin'];
        $managed_plugins = get_option('puc_managed_plugins', array());

        // Só verifica plugins gerenciados
        if (!in_array($plugin_file, $managed_plugins)) {
            return $response;
        }

        $parts = explode('/', $plugin_file);
        $plugin_slug = $parts[0];

        // Aguarda um momento para o WordPress processar
        sleep(1);

        // Executa health check
        $health_result = $this->perform_health_check();

        if (!$health_result['healthy']) {
            $this->log("Health check falhou após atualização de {$plugin_slug}: " . $health_result['message']);
            
            // Tenta rollback automático
            $backup_info = get_transient('puc_current_backup_' . $plugin_slug);
            
            if ($backup_info && !empty($backup_info['path'])) {
                $this->log("Iniciando rollback automático para {$plugin_slug}");
                
                $rollback_result = $this->perform_rollback($plugin_slug, $backup_info['path']);
                
                if ($rollback_result['success']) {
                    $this->log("Rollback concluído com sucesso para {$plugin_slug}");
                    
                    // Notifica servidor sobre o rollback
                    $this->report_update_rollback(
                        $plugin_slug, 
                        $backup_info['version'], 
                        $backup_info['to_version'] ?? 'unknown',
                        $health_result['message'],
                        true // automático
                    );
                    
                    // Notifica o admin
                    $this->send_rollback_notification($plugin_slug, $backup_info['version'], $health_result['message']);
                    
                    // Armazena informação do rollback para exibir aviso
                    set_transient('puc_rollback_notice', array(
                        'plugin' => $plugin_slug,
                        'version' => $backup_info['version'],
                        'reason' => $health_result['message'],
                        'time' => time()
                    ), DAY_IN_SECONDS);
                    
                } else {
                    $this->log("Falha no rollback para {$plugin_slug}: " . $rollback_result['message']);
                    
                    // Notifica servidor sobre erro no rollback
                    $this->report_update_error(
                        $plugin_slug,
                        $backup_info['to_version'] ?? 'unknown',
                        'Rollback também falhou: ' . $rollback_result['message'],
                        'rollback_failed'
                    );
                    
                    // Notifica erro crítico
                    $this->send_critical_error_notification($plugin_slug, $rollback_result['message']);
                }
            } else {
                // Não tinha backup, reporta erro
                $this->report_update_error(
                    $plugin_slug,
                    'unknown',
                    $health_result['message'],
                    'health_check_failed'
                );
            }
        } else {
            $this->log("Health check passou após atualização de {$plugin_slug}");
            
            // Notifica servidor sobre sucesso
            $backup_info = get_transient('puc_current_backup_' . $plugin_slug);
            $this->report_update_success(
                $plugin_slug,
                $backup_info['to_version'] ?? 'unknown',
                true // health check passou
            );
        }

        // Limpa transient do backup atual
        delete_transient('puc_current_backup_' . $plugin_slug);

        return $response;
    }

    /**
     * Cria backup de um plugin
     */
    public function create_backup($plugin_slug, $version) {
        $source_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
        
        if (!is_dir($source_dir)) {
            return new WP_Error('plugin_not_found', 'Plugin não encontrado: ' . $plugin_slug);
        }

        // Nome do arquivo de backup com timestamp
        $backup_name = $plugin_slug . '_v' . $version . '_' . date('Y-m-d_H-i-s');
        $backup_path = $this->backup_dir . '/' . $backup_name;

        // Copia o diretório do plugin
        $copy_result = $this->recursive_copy($source_dir, $backup_path);

        if (!$copy_result) {
            return new WP_Error('backup_failed', 'Falha ao copiar arquivos do plugin');
        }

        // Cria arquivo de metadados
        $meta = array(
            'plugin_slug' => $plugin_slug,
            'version' => $version,
            'created_at' => date('Y-m-d H:i:s'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'site_url' => home_url('/')
        );
        
        file_put_contents($backup_path . '/puc-backup-meta.json', json_encode($meta, JSON_PRETTY_PRINT));

        return $backup_path;
    }

    /**
     * Copia diretório recursivamente
     */
    private function recursive_copy($source, $dest) {
        if (!is_dir($dest)) {
            if (!wp_mkdir_p($dest)) {
                return false;
            }
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $dest_path = $dest . '/' . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($dest_path)) {
                    wp_mkdir_p($dest_path);
                }
            } else {
                copy($item->getPathname(), $dest_path);
            }
        }

        return true;
    }

    /**
     * Executa health check do site
     */
    public function perform_health_check() {
        $checks = array();
        $healthy = true;
        $messages = array();

        // 1. Verifica se o WordPress está carregando
        $checks['wp_load'] = $this->check_wp_load();
        if (!$checks['wp_load']['pass']) {
            $healthy = false;
            $messages[] = $checks['wp_load']['message'];
        }

        // 2. Verifica erros fatais de PHP
        $checks['php_errors'] = $this->check_php_errors();
        if (!$checks['php_errors']['pass']) {
            $healthy = false;
            $messages[] = $checks['php_errors']['message'];
        }

        // 3. Verifica se o frontend está acessível
        $checks['frontend'] = $this->check_frontend_accessible();
        if (!$checks['frontend']['pass']) {
            $healthy = false;
            $messages[] = $checks['frontend']['message'];
        }

        // 4. Verifica se o admin está acessível
        $checks['admin'] = $this->check_admin_accessible();
        if (!$checks['admin']['pass']) {
            $healthy = false;
            $messages[] = $checks['admin']['message'];
        }

        // 5. Verifica erros no log (recentes)
        $checks['error_log'] = $this->check_recent_errors();
        if (!$checks['error_log']['pass']) {
            $healthy = false;
            $messages[] = $checks['error_log']['message'];
        }

        return array(
            'healthy' => $healthy,
            'message' => $healthy ? 'Todas as verificações passaram' : implode('; ', $messages),
            'checks' => $checks,
            'timestamp' => time()
        );
    }

    /**
     * Verifica se o WordPress carrega corretamente
     */
    private function check_wp_load() {
        // Verifica se funções básicas do WP estão disponíveis
        if (!function_exists('get_bloginfo') || !function_exists('wp_remote_get')) {
            return array(
                'pass' => false,
                'message' => 'Funções básicas do WordPress não disponíveis'
            );
        }

        return array('pass' => true, 'message' => 'WordPress carregado');
    }

    /**
     * Verifica erros fatais de PHP
     */
    private function check_php_errors() {
        // Verifica o último erro
        $last_error = error_get_last();
        
        if ($last_error && in_array($last_error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            return array(
                'pass' => false,
                'message' => 'Erro fatal de PHP: ' . $last_error['message']
            );
        }

        return array('pass' => true, 'message' => 'Sem erros fatais de PHP');
    }

    /**
     * Verifica se o frontend está acessível
     */
    private function check_frontend_accessible() {
        $home_url = home_url('/');
        
        $response = wp_remote_get($home_url, array(
            'timeout' => $this->health_check_timeout,
            'sslverify' => false,
            'headers' => array(
                'Cache-Control' => 'no-cache'
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'pass' => false,
                'message' => 'Frontend inacessível: ' . $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        
        // Códigos de erro 5xx indicam problemas no servidor
        if ($code >= 500) {
            return array(
                'pass' => false,
                'message' => 'Frontend retornou erro HTTP ' . $code
            );
        }

        // Verifica se a resposta contém indicadores de erro
        $body = wp_remote_retrieve_body($response);
        
        if ($this->contains_php_error($body)) {
            return array(
                'pass' => false,
                'message' => 'Frontend contém erros de PHP'
            );
        }

        return array('pass' => true, 'message' => 'Frontend acessível');
    }

    /**
     * Verifica se o admin está acessível
     */
    private function check_admin_accessible() {
        $admin_url = admin_url('admin-ajax.php');
        
        $response = wp_remote_post($admin_url, array(
            'timeout' => $this->health_check_timeout,
            'sslverify' => false,
            'body' => array(
                'action' => 'puc_health_check',
                'check' => 'ping'
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'pass' => false,
                'message' => 'Admin AJAX inacessível: ' . $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 500) {
            return array(
                'pass' => false,
                'message' => 'Admin retornou erro HTTP ' . $code
            );
        }

        $body = wp_remote_retrieve_body($response);
        
        if ($this->contains_php_error($body)) {
            return array(
                'pass' => false,
                'message' => 'Admin contém erros de PHP'
            );
        }

        return array('pass' => true, 'message' => 'Admin acessível');
    }

    /**
     * Verifica erros recentes no log
     */
    private function check_recent_errors() {
        $error_log = ini_get('error_log');
        
        if (empty($error_log) || !file_exists($error_log)) {
            // Tenta o log padrão do WordPress
            $error_log = WP_CONTENT_DIR . '/debug.log';
        }

        if (!file_exists($error_log) || !is_readable($error_log)) {
            // Não consegue verificar, assume OK
            return array('pass' => true, 'message' => 'Log não disponível para verificação');
        }

        // Lê as últimas linhas do log
        $lines = $this->tail_file($error_log, 50);
        
        if (empty($lines)) {
            return array('pass' => true, 'message' => 'Nenhum erro recente no log');
        }

        // Procura por erros fatais nos últimos 30 segundos
        $now = time();
        $fatal_patterns = array(
            'Fatal error',
            'Parse error',
            'Uncaught Error',
            'Uncaught Exception'
        );

        foreach ($lines as $line) {
            // Verifica se é erro recente (nos últimos 30 segundos)
            if (preg_match('/\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $log_time = strtotime($matches[1]);
                
                if ($log_time && ($now - $log_time) <= 30) {
                    foreach ($fatal_patterns as $pattern) {
                        if (stripos($line, $pattern) !== false) {
                            return array(
                                'pass' => false,
                                'message' => 'Erro fatal recente detectado no log'
                            );
                        }
                    }
                }
            }
        }

        return array('pass' => true, 'message' => 'Sem erros fatais recentes');
    }

    /**
     * Verifica se o HTML contém indicadores de erro PHP
     */
    private function contains_php_error($html) {
        $error_patterns = array(
            'Fatal error:',
            'Parse error:',
            'Warning:',
            'Notice:',
            'Uncaught Error:',
            'Uncaught Exception:',
            'Call to undefined function',
            'Class \'[^\']+\' not found',
            'require_once\(\): Failed opening'
        );

        foreach ($error_patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $html)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lê as últimas N linhas de um arquivo
     */
    private function tail_file($filepath, $lines = 50) {
        $file = new SplFileObject($filepath, 'r');
        $file->seek(PHP_INT_MAX);
        $last_line = $file->key();
        
        $start = max(0, $last_line - $lines);
        $file->seek($start);
        
        $result = array();
        while (!$file->eof()) {
            $result[] = $file->fgets();
        }
        
        return $result;
    }

    /**
     * Executa rollback de um plugin
     */
    public function perform_rollback($plugin_slug, $backup_path) {
        if (!is_dir($backup_path)) {
            return array(
                'success' => false,
                'message' => 'Backup não encontrado: ' . $backup_path
            );
        }

        $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;

        // Desativa o plugin antes do rollback
        $plugin_file = $this->get_main_plugin_file($plugin_slug);
        if ($plugin_file && is_plugin_active($plugin_file)) {
            deactivate_plugins($plugin_file);
        }

        // Remove o diretório atual do plugin
        if (is_dir($plugin_dir)) {
            $this->recursive_delete($plugin_dir);
        }

        // Restaura do backup
        $copy_result = $this->recursive_copy($backup_path, $plugin_dir);

        if (!$copy_result) {
            return array(
                'success' => false,
                'message' => 'Falha ao restaurar arquivos do backup'
            );
        }

        // Remove arquivo de metadados do diretório restaurado
        $meta_file = $plugin_dir . '/puc-backup-meta.json';
        if (file_exists($meta_file)) {
            unlink($meta_file);
        }

        // Reativa o plugin
        if ($plugin_file) {
            activate_plugin($plugin_file);
        }

        return array(
            'success' => true,
            'message' => 'Rollback concluído com sucesso'
        );
    }

    /**
     * Obtém o arquivo principal do plugin pelo slug
     */
    private function get_main_plugin_file($plugin_slug) {
        $plugins = get_plugins();
        
        foreach ($plugins as $file => $data) {
            if (strpos($file, $plugin_slug . '/') === 0) {
                return $file;
            }
        }
        
        return null;
    }

    /**
     * Remove diretório recursivamente
     */
    private function recursive_delete($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->recursive_delete($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Envia notificação de rollback por email
     */
    private function send_rollback_notification($plugin_slug, $version, $reason) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(
            '[%s] Rollback automático executado: %s',
            $site_name,
            $plugin_slug
        );
        
        $message = sprintf(
            "Olá,\n\n" .
            "O sistema de atualizações detectou um problema após a atualização do plugin '%s' e executou um rollback automático para a versão %s.\n\n" .
            "Motivo: %s\n\n" .
            "Site: %s\n\n" .
            "Recomendamos verificar se há atualizações de compatibilidade disponíveis antes de tentar atualizar novamente.\n\n" .
            "Este é um email automático do Premium Updates Client.",
            $plugin_slug,
            $version,
            $reason,
            home_url('/')
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Envia notificação de erro crítico
     */
    private function send_critical_error_notification($plugin_slug, $error) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(
            '[%s] ERRO CRÍTICO: Falha no rollback de %s',
            $site_name,
            $plugin_slug
        );
        
        $message = sprintf(
            "ATENÇÃO!\n\n" .
            "O sistema detectou um problema após a atualização do plugin '%s' e tentou executar um rollback automático, mas o rollback também falhou.\n\n" .
            "Erro: %s\n\n" .
            "Site: %s\n\n" .
            "AÇÃO NECESSÁRIA: Verifique seu site imediatamente e restaure manualmente se necessário.\n\n" .
            "Você pode encontrar os backups em: wp-content/puc-backups/\n\n" .
            "Este é um email automático do Premium Updates Client.",
            $plugin_slug,
            $error,
            home_url('/')
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Limpa backups antigos (mantém últimos 3 de cada plugin)
     */
    public function cleanup_old_backups($upgrader, $hook_extra) {
        $backups = $this->get_all_backups();
        
        // Agrupa por plugin
        $by_plugin = array();
        foreach ($backups as $backup) {
            $slug = $backup['plugin_slug'];
            if (!isset($by_plugin[$slug])) {
                $by_plugin[$slug] = array();
            }
            $by_plugin[$slug][] = $backup;
        }
        
        // Mantém apenas os 3 mais recentes de cada plugin
        foreach ($by_plugin as $slug => $plugin_backups) {
            // Ordena por data (mais recente primeiro)
            usort($plugin_backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Remove backups extras
            $to_remove = array_slice($plugin_backups, 3);
            
            foreach ($to_remove as $backup) {
                $this->recursive_delete($backup['path']);
                $this->log("Backup antigo removido: " . $backup['path']);
            }
        }
    }

    /**
     * Obtém todos os backups
     */
    public function get_all_backups() {
        $backups = array();
        
        if (!is_dir($this->backup_dir)) {
            return $backups;
        }

        $dirs = scandir($this->backup_dir);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $backup_path = $this->backup_dir . '/' . $dir;
            $meta_file = $backup_path . '/puc-backup-meta.json';
            
            if (is_dir($backup_path) && file_exists($meta_file)) {
                $meta = json_decode(file_get_contents($meta_file), true);
                
                if ($meta) {
                    $meta['path'] = $backup_path;
                    $meta['dir_name'] = $dir;
                    $meta['size'] = $this->get_dir_size($backup_path);
                    $backups[] = $meta;
                }
            }
        }
        
        // Ordena por data (mais recente primeiro)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }

    /**
     * Calcula tamanho de um diretório
     */
    private function get_dir_size($dir) {
        $size = 0;
        
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }

    /**
     * Formata tamanho em bytes para humano
     */
    public function format_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Registra endpoint REST para health check
     */
    public function register_health_check_endpoint() {
        register_rest_route('puc/v1', '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_health_check'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * REST API: Health check
     */
    public function rest_health_check($request) {
        $result = $this->perform_health_check();
        
        return new WP_REST_Response($result, $result['healthy'] ? 200 : 503);
    }

    /**
     * AJAX: Health check interno
     */
    public function ajax_health_check() {
        // Resposta simples para verificação de funcionamento
        wp_send_json_success(array(
            'status' => 'ok',
            'time' => time()
        ));
    }

    /**
     * AJAX: Rollback manual
     */
    public function ajax_manual_rollback() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $backup_dir = isset($_POST['backup_dir']) ? sanitize_file_name($_POST['backup_dir']) : '';
        
        if (empty($backup_dir)) {
            wp_send_json_error(__('Backup não especificado', 'premium-updates-client'));
        }

        $backup_path = $this->backup_dir . '/' . $backup_dir;
        $meta_file = $backup_path . '/puc-backup-meta.json';
        
        if (!file_exists($meta_file)) {
            wp_send_json_error(__('Backup não encontrado', 'premium-updates-client'));
        }

        $meta = json_decode(file_get_contents($meta_file), true);
        
        if (!$meta || empty($meta['plugin_slug'])) {
            wp_send_json_error(__('Metadados do backup inválidos', 'premium-updates-client'));
        }

        $result = $this->perform_rollback($meta['plugin_slug'], $backup_path);

        if ($result['success']) {
            // Reporta rollback manual ao servidor
            $this->report_update_rollback(
                $meta['plugin_slug'],
                $meta['version'],
                'manual',
                'Rollback manual executado pelo administrador',
                false // manual
            );
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Plugin %s restaurado para versão %s', 'premium-updates-client'),
                    $meta['plugin_slug'],
                    $meta['version']
                )
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX: Listar backups
     */
    public function ajax_get_backups() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $backups = $this->get_all_backups();
        
        // Formata para exibição
        foreach ($backups as &$backup) {
            $backup['size_formatted'] = $this->format_size($backup['size']);
            $backup['created_at_formatted'] = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($backup['created_at'])
            );
        }

        wp_send_json_success($backups);
    }

    /**
     * AJAX: Excluir backup
     */
    public function ajax_delete_backup() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $backup_dir = isset($_POST['backup_dir']) ? sanitize_file_name($_POST['backup_dir']) : '';
        
        if (empty($backup_dir)) {
            wp_send_json_error(__('Backup não especificado', 'premium-updates-client'));
        }

        $backup_path = $this->backup_dir . '/' . $backup_dir;
        
        if (!is_dir($backup_path)) {
            wp_send_json_error(__('Backup não encontrado', 'premium-updates-client'));
        }

        $this->recursive_delete($backup_path);

        wp_send_json_success(array(
            'message' => __('Backup excluído com sucesso', 'premium-updates-client')
        ));
    }

    /**
     * Log de eventos
     */
    private function log($message) {
        $log_file = $this->backup_dir . '/safe-updater.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] {$message}\n";
        
        file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
        
        // Limita tamanho do log (máximo 100KB)
        if (file_exists($log_file) && filesize($log_file) > 102400) {
            $lines = file($log_file);
            $lines = array_slice($lines, -500); // Mantém últimas 500 linhas
            file_put_contents($log_file, implode('', $lines));
        }
    }
    
    /**
     * Reporta início de atualização ao servidor
     */
    private function report_update_started($plugin_slug, $from_version, $to_version) {
        $this->send_update_status('update/started', array(
            'plugin_slug' => $plugin_slug,
            'from_version' => $from_version,
            'to_version' => $to_version,
            'site_url' => home_url('/'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        ));
    }
    
    /**
     * Reporta sucesso de atualização ao servidor
     */
    private function report_update_success($plugin_slug, $to_version, $health_check_passed = true) {
        $this->send_update_status('update/success', array(
            'plugin_slug' => $plugin_slug,
            'to_version' => $to_version,
            'health_check_passed' => $health_check_passed
        ));
    }
    
    /**
     * Reporta erro de atualização ao servidor
     */
    private function report_update_error($plugin_slug, $to_version, $error_message, $error_type = 'unknown') {
        $this->send_update_status('update/error', array(
            'plugin_slug' => $plugin_slug,
            'to_version' => $to_version,
            'error_message' => $error_message,
            'error_type' => $error_type,
            'health_check_passed' => false
        ));
    }
    
    /**
     * Reporta rollback ao servidor
     */
    private function report_update_rollback($plugin_slug, $from_version, $to_version, $error_message, $automatic = true) {
        $this->send_update_status('update/rollback', array(
            'plugin_slug' => $plugin_slug,
            'from_version' => $from_version,
            'to_version' => $to_version,
            'error_message' => $error_message,
            'automatic' => $automatic
        ));
    }
    
    /**
     * Envia status de atualização para o servidor
     */
    private function send_update_status($endpoint, $data) {
        $server_url = get_option('puc_server_url', '');
        $license_key = get_option('puc_license_key', '');
        
        if (empty($server_url) || empty($license_key)) {
            $this->log('Não foi possível reportar status: configuração incompleta');
            return false;
        }
        
        $url = rtrim($server_url, '/') . '/api/v1/' . $endpoint;
        
        $data['license_key'] = $license_key;
        $data['site_url'] = home_url('/');
        
        $response = wp_remote_post($url, array(
            'timeout' => 15,
            'sslverify' => false,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data)
        ));
        
        if (is_wp_error($response)) {
            $this->log('Erro ao reportar status ao servidor: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            $this->log('Status reportado ao servidor: ' . $endpoint);
            return true;
        }
        
        $this->log('Servidor rejeitou status: ' . ($body['message'] ?? 'erro desconhecido'));
        return false;
    }

    /**
     * Exibe aviso de rollback no admin
     */
    public function rollback_admin_notice() {
        $notice = get_transient('puc_rollback_notice');
        
        if ($notice) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Premium Updates - Rollback Automático:', 'premium-updates-client') . '</strong> ';
            echo sprintf(
                __('O plugin %s foi revertido para a versão %s devido a: %s', 'premium-updates-client'),
                '<code>' . esc_html($notice['plugin']) . '</code>',
                '<code>' . esc_html($notice['version']) . '</code>',
                esc_html($notice['reason'])
            );
            echo '</p></div>';
            
            // Remove o aviso após exibir
            delete_transient('puc_rollback_notice');
        }
    }
}

// Inicializa
add_action('plugins_loaded', function() {
    PUC_Safe_Updater::get_instance();
});

// Adiciona aviso de rollback
add_action('admin_notices', array(PUC_Safe_Updater::get_instance(), 'rollback_admin_notice'));
