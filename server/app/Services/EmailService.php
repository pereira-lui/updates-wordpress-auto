<?php

namespace App\Services;

use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Serviço de Email
 */
class EmailService {
    
    private static $instance = null;
    private $settings = [];
    
    private function __construct() {
        $this->loadSettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Carrega configurações SMTP
     */
    private function loadSettings() {
        $this->settings = [
            'host' => get_setting('smtp_host', ''),
            'port' => get_setting('smtp_port', '587'),
            'user' => get_setting('smtp_user', ''),
            'pass' => get_setting('smtp_pass', ''),
            'from' => get_setting('smtp_from', ''),
            'from_name' => get_setting('smtp_from_name', 'Premium Updates'),
            'encryption' => get_setting('smtp_encryption', 'tls'),
        ];
    }
    
    /**
     * Verifica se SMTP está configurado
     */
    public function isConfigured() {
        return !empty($this->settings['host']) && 
               !empty($this->settings['user']) && 
               !empty($this->settings['pass']);
    }
    
    /**
     * Envia email
     */
    public function send($to, $subject, $body, $options = []) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'SMTP não configurado'];
        }
        
        require_once APP_PATH . '/vendor/PHPMailer/PHPMailer.php';
        require_once APP_PATH . '/vendor/PHPMailer/SMTP.php';
        require_once APP_PATH . '/vendor/PHPMailer/Exception.php';
        
        $mail = new PHPMailer(true);
        
        try {
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $this->settings['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings['user'];
            $mail->Password = $this->settings['pass'];
            $mail->SMTPSecure = $this->settings['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)$this->settings['port'];
            $mail->CharSet = 'UTF-8';
            
            // Remetente
            $mail->setFrom(
                $this->settings['from'] ?: $this->settings['user'],
                $this->settings['from_name']
            );
            
            // Destinatário
            if (is_array($to)) {
                $mail->addAddress($to['email'], $to['name'] ?? '');
            } else {
                $mail->addAddress($to);
            }
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->wrapInTemplate($body, $subject);
            $mail->AltBody = strip_tags($body);
            
            $mail->send();
            
            // Log de sucesso
            $this->logEmail($options['license_id'] ?? null, $to, $subject, $options['type'] ?? 'general', 'sent');
            
            return ['success' => true, 'message' => 'Email enviado'];
            
        } catch (Exception $e) {
            // Log de erro
            $this->logEmail(
                $options['license_id'] ?? null, 
                is_array($to) ? $to['email'] : $to, 
                $subject, 
                $options['type'] ?? 'general', 
                'failed', 
                $mail->ErrorInfo
            );
            
            return ['success' => false, 'message' => $mail->ErrorInfo];
        }
    }
    
    /**
     * Envolve o conteúdo em um template HTML
     */
    private function wrapInTemplate($content, $title) {
        $siteName = get_setting('site_name', 'Premium Updates');
        $siteUrl = get_setting('site_url', url('/'));
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($siteName) . '</h1>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '</p>
            <p><a href="' . htmlspecialchars($siteUrl) . '">' . htmlspecialchars($siteUrl) . '</a></p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Log de email
     */
    private function logEmail($licenseId, $toEmail, $subject, $type, $status, $error = null) {
        try {
            Database::insert('email_logs', [
                'license_id' => $licenseId,
                'to_email' => is_array($toEmail) ? $toEmail['email'] : $toEmail,
                'subject' => $subject,
                'type' => $type,
                'status' => $status,
                'error_message' => $error,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }
    
    /**
     * Adiciona email à fila
     */
    public function queue($to, $subject, $body, $template = null) {
        return Database::insert('email_queue', [
            'to_email' => is_array($to) ? $to['email'] : $to,
            'to_name' => is_array($to) ? ($to['name'] ?? null) : null,
            'subject' => $subject,
            'body' => $body,
            'template' => $template,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Processa fila de emails
     */
    public function processQueue($limit = 10) {
        $emails = Database::select(
            "SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT ?",
            [$limit]
        );
        
        $results = ['sent' => 0, 'failed' => 0];
        
        foreach ($emails as $email) {
            $to = ['email' => $email->to_email, 'name' => $email->to_name];
            $result = $this->send($to, $email->subject, $email->body);
            
            if ($result['success']) {
                Database::update('email_queue', [
                    'status' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$email->id]);
                $results['sent']++;
            } else {
                Database::update('email_queue', [
                    'attempts' => $email->attempts + 1,
                    'last_error' => $result['message'],
                    'status' => $email->attempts >= 2 ? 'failed' : 'pending'
                ], 'id = ?', [$email->id]);
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Envia notificação de atualização ao admin
     */
    public function notifyAdminUpdate($license, $plugin, $status, $details = []) {
        $adminEmail = get_setting('notify_admin_email', get_setting('admin_email'));
        
        // Verifica se admin quer notificações para este tipo
        if ($status === 'success' && get_setting('notify_admin_updates', '1') !== '1') {
            return false;
        }
        if ($status === 'error' && get_setting('notify_admin_errors', '1') !== '1') {
            return false;
        }
        if ($status === 'rollback' && get_setting('notify_admin_rollbacks', '1') !== '1') {
            return false;
        }
        
        if (empty($adminEmail)) {
            return false;
        }
        
        $statusLabels = [
            'success' => '✅ Sucesso',
            'error' => '❌ Erro',
            'rollback' => '↩️ Rollback'
        ];
        
        $statusColors = [
            'success' => 'success',
            'error' => 'danger',
            'rollback' => 'warning'
        ];
        
        $subject = "[Atualização] {$statusLabels[$status]} - {$license->client_name} ({$plugin})";
        
        $body = "<h2>Notificação de Atualização</h2>";
        $body .= "<div class='alert alert-{$statusColors[$status]}'>";
        $body .= "<strong>Status:</strong> {$statusLabels[$status]}";
        $body .= "</div>";
        
        $body .= "<table>";
        $body .= "<tr><th>Cliente</th><td>{$license->client_name}</td></tr>";
        $body .= "<tr><th>Email</th><td>{$license->client_email}</td></tr>";
        $body .= "<tr><th>Site</th><td>{$license->site_url}</td></tr>";
        $body .= "<tr><th>Plugin</th><td>{$plugin}</td></tr>";
        
        if (!empty($details['from_version'])) {
            $body .= "<tr><th>Versão Anterior</th><td>{$details['from_version']}</td></tr>";
        }
        if (!empty($details['to_version'])) {
            $body .= "<tr><th>Nova Versão</th><td>{$details['to_version']}</td></tr>";
        }
        if (!empty($details['error_message'])) {
            $body .= "<tr><th>Erro</th><td><code>{$details['error_message']}</code></td></tr>";
        }
        
        $body .= "<tr><th>Data/Hora</th><td>" . date('d/m/Y H:i:s') . "</td></tr>";
        $body .= "</table>";
        
        $body .= "<p><a href='" . url('/admin/licenses/' . $license->id) . "' class='button'>Ver Detalhes da Licença</a></p>";
        
        return $this->send($adminEmail, $subject, $body, [
            'license_id' => $license->id,
            'type' => 'admin_update_notification'
        ]);
    }
    
    /**
     * Envia notificação de atualização ao cliente
     */
    public function notifyClientUpdate($license, $plugin, $status, $details = []) {
        // Verifica preferências do cliente
        if ($status === 'success' && !$license->notify_on_update) {
            return false;
        }
        if ($status === 'error' && !$license->notify_on_error) {
            return false;
        }
        if ($status === 'rollback' && !$license->notify_on_rollback) {
            return false;
        }
        
        $toEmail = $license->notification_email ?: $license->client_email;
        
        if (empty($toEmail)) {
            return false;
        }
        
        $statusLabels = [
            'success' => 'concluída com sucesso',
            'error' => 'encontrou um erro',
            'rollback' => 'foi revertida'
        ];
        
        $statusColors = [
            'success' => 'success',
            'error' => 'danger',
            'rollback' => 'warning'
        ];
        
        $subject = "[{$plugin}] Atualização {$statusLabels[$status]}";
        
        $body = "<h2>Olá, {$license->client_name}!</h2>";
        
        if ($status === 'success') {
            $body .= "<div class='alert alert-success'>";
            $body .= "<strong>Ótimas notícias!</strong> A atualização do plugin <strong>{$plugin}</strong> foi concluída com sucesso.";
            $body .= "</div>";
            
            if (!empty($details['to_version'])) {
                $body .= "<p>Seu plugin foi atualizado para a versão <strong>{$details['to_version']}</strong>.</p>";
            }
            
        } elseif ($status === 'error') {
            $body .= "<div class='alert alert-danger'>";
            $body .= "<strong>Atenção!</strong> A atualização do plugin <strong>{$plugin}</strong> encontrou um erro.";
            $body .= "</div>";
            
            if (!empty($details['error_message'])) {
                $body .= "<p><strong>Erro:</strong> <code>{$details['error_message']}</code></p>";
            }
            
            $body .= "<p>Nossa equipe já foi notificada e está verificando o problema.</p>";
            
        } elseif ($status === 'rollback') {
            $body .= "<div class='alert alert-warning'>";
            $body .= "<strong>Aviso!</strong> A atualização do plugin <strong>{$plugin}</strong> foi revertida automaticamente.";
            $body .= "</div>";
            
            $body .= "<p>Detectamos um problema após a atualização e seu site foi restaurado para a versão anterior para garantir que tudo continue funcionando.</p>";
            
            if (!empty($details['error_message'])) {
                $body .= "<p><strong>Motivo:</strong> {$details['error_message']}</p>";
            }
        }
        
        $body .= "<table>";
        $body .= "<tr><th>Site</th><td>{$license->site_url}</td></tr>";
        $body .= "<tr><th>Plugin</th><td>{$plugin}</td></tr>";
        
        if (!empty($details['from_version'])) {
            $body .= "<tr><th>Versão Anterior</th><td>{$details['from_version']}</td></tr>";
        }
        if (!empty($details['to_version'])) {
            $body .= "<tr><th>Versão Atualizada</th><td>{$details['to_version']}</td></tr>";
        }
        
        $body .= "<tr><th>Data/Hora</th><td>" . date('d/m/Y H:i:s') . "</td></tr>";
        $body .= "</table>";
        
        $body .= "<p style='color: #666; font-size: 12px;'>Você está recebendo este email porque ativou as notificações de atualização. Para alterar suas preferências, acesse o painel do plugin no seu WordPress.</p>";
        
        return $this->send($toEmail, $subject, $body, [
            'license_id' => $license->id,
            'type' => 'client_update_notification'
        ]);
    }
    
    /**
     * Testa configuração SMTP
     */
    public function testConnection($to = null) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'SMTP não configurado'];
        }
        
        $testTo = $to ?: $this->settings['from'] ?: $this->settings['user'];
        
        return $this->send(
            $testTo,
            'Teste de Conexão SMTP - Premium Updates',
            '<h2>Teste de Email</h2><p>Se você está vendo esta mensagem, a configuração SMTP está funcionando corretamente!</p><p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>',
            ['type' => 'test']
        );
    }
}
