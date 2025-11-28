<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Plugins
 */
class Plugin {
    
    public static function all($activeOnly = false) {
        $sql = "SELECT * FROM plugins";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        
        return Database::select($sql);
    }
    
    public static function find($id) {
        return Database::selectOne("SELECT * FROM plugins WHERE id = ?", [$id]);
    }
    
    public static function findBySlug($slug) {
        return Database::selectOne("SELECT * FROM plugins WHERE slug = ?", [$slug]);
    }
    
    public static function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert('plugins', $data);
    }
    
    public static function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update('plugins', $data, 'id = ?', [$id]);
    }
    
    public static function delete($id) {
        return Database::delete('plugins', 'id = ?', [$id]);
    }
    
    public static function getVersionInfo($slug) {
        $plugin = self::findBySlug($slug);
        if (!$plugin) {
            return null;
        }
        
        return [
            'name' => $plugin->name,
            'slug' => $plugin->slug,
            'version' => $plugin->version,
            'download_url' => url('/api/v1/download/' . $plugin->slug),
            'requires' => $plugin->requires_wp,
            'tested' => $plugin->tested_wp,
            'requires_php' => $plugin->requires_php,
            'last_updated' => $plugin->updated_at ?? $plugin->created_at,
            'author' => $plugin->author,
            'author_uri' => $plugin->author_uri,
            'sections' => [
                'description' => $plugin->description,
                'changelog' => $plugin->changelog
            ]
        ];
    }
    
    public static function incrementDownloads($id) {
        return Database::query(
            "UPDATE plugins SET downloads = downloads + 1 WHERE id = ?",
            [$id]
        );
    }
    
    public static function uploadZip($id, $file) {
        $plugin = self::find($id);
        if (!$plugin) {
            return ['success' => false, 'message' => 'Plugin não encontrado'];
        }
        
        $uploadDir = config('uploads.path') . '/plugins/' . $plugin->slug;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Remove arquivo antigo
        if ($plugin->zip_file && file_exists($uploadDir . '/' . $plugin->zip_file)) {
            unlink($uploadDir . '/' . $plugin->zip_file);
        }
        
        $filename = $plugin->slug . '-' . $plugin->version . '.zip';
        $destination = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            self::update($id, ['zip_file' => $filename]);
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Erro ao fazer upload'];
    }
    
    public static function getZipPath($slug) {
        $plugin = self::findBySlug($slug);
        if (!$plugin || !$plugin->zip_file) {
            return null;
        }
        
        $path = config('uploads.path') . '/plugins/' . $slug . '/' . $plugin->zip_file;
        return file_exists($path) ? $path : null;
    }
    
    public static function count() {
        $result = Database::selectOne("SELECT COUNT(*) as total FROM plugins");
        return $result ? $result->total : 0;
    }
}
