<?php
/**
 * Classe para gerenciar os plugins no servidor
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_Plugin_Manager {

    /**
     * Escaneia um plugin ZIP e extrai suas informações
     */
    public static function parse_plugin_zip($zip_path) {
        if (!file_exists($zip_path)) {
            return new WP_Error('file_not_found', 'Arquivo ZIP não encontrado');
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            return new WP_Error('invalid_zip', 'Não foi possível abrir o arquivo ZIP');
        }

        $plugin_data = null;
        $main_file = null;

        // Procura pelo arquivo principal do plugin
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Procura por arquivos PHP no primeiro nível da pasta
            if (preg_match('/^([^\/]+)\/([^\/]+\.php)$/', $filename, $matches)) {
                $content = $zip->getFromIndex($i);
                
                // Verifica se é o arquivo principal do plugin
                if (strpos($content, 'Plugin Name:') !== false) {
                    $plugin_data = self::parse_plugin_header($content);
                    $plugin_data['slug'] = $matches[1];
                    $main_file = $filename;
                    break;
                }
            }
        }

        $zip->close();

        if (!$plugin_data) {
            return new WP_Error('no_plugin_found', 'Nenhum plugin válido encontrado no ZIP');
        }

        return $plugin_data;
    }

    /**
     * Extrai informações do cabeçalho do plugin
     */
    public static function parse_plugin_header($content) {
        $headers = array(
            'name' => 'Plugin Name',
            'version' => 'Version',
            'author' => 'Author',
            'description' => 'Description',
            'plugin_uri' => 'Plugin URI',
            'requires_wp' => 'Requires at least',
            'tested_wp' => 'Tested up to',
            'requires_php' => 'Requires PHP'
        );

        $data = array();

        foreach ($headers as $key => $header) {
            if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+)$/mi', $content, $matches)) {
                $data[$key] = trim($matches[1]);
            } else {
                $data[$key] = '';
            }
        }

        return $data;
    }

    /**
     * Move um ZIP de plugin para a pasta de packages
     */
    public static function store_plugin_package($file, $slug) {
        $upload_dir = wp_upload_dir();
        $packages_dir = $upload_dir['basedir'] . '/pus-packages';

        if (!file_exists($packages_dir)) {
            wp_mkdir_p($packages_dir);
            
            // Protege a pasta
            file_put_contents($packages_dir . '/.htaccess', 'deny from all');
            file_put_contents($packages_dir . '/index.php', '<?php // Silence is golden');
        }

        $destination = $packages_dir . '/' . $slug . '.zip';

        if (is_uploaded_file($file['tmp_name'])) {
            move_uploaded_file($file['tmp_name'], $destination);
        } else {
            copy($file['tmp_name'], $destination);
        }

        return $upload_dir['baseurl'] . '/pus-packages/' . $slug . '.zip';
    }

    /**
     * Gera uma URL segura para download
     */
    public static function get_secure_download_url($slug) {
        $upload_dir = wp_upload_dir();
        $package_path = $upload_dir['basedir'] . '/pus-packages/' . $slug . '.zip';

        if (!file_exists($package_path)) {
            return false;
        }

        // Gera um token temporário
        $token = wp_generate_password(32, false);
        $expiry = time() + 3600; // 1 hora

        set_transient('pus_download_' . $token, array(
            'slug' => $slug,
            'expiry' => $expiry
        ), 3600);

        return add_query_arg(array(
            'pus_download' => $token
        ), home_url('/'));
    }
}

// Handler para downloads seguros
add_action('init', function() {
    if (isset($_GET['pus_download'])) {
        $token = sanitize_text_field($_GET['pus_download']);
        $data = get_transient('pus_download_' . $token);

        if (!$data || $data['expiry'] < time()) {
            wp_die('Link de download expirado ou inválido');
        }

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/pus-packages/' . $data['slug'] . '.zip';

        if (!file_exists($file_path)) {
            wp_die('Arquivo não encontrado');
        }

        // Limpa o token após uso
        delete_transient('pus_download_' . $token);

        // Envia o arquivo
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $data['slug'] . '.zip"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
});
