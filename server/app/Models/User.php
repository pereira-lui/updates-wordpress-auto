<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Usuários Administrativos
 */
class User {
    
    public static function all() {
        return Database::select(
            "SELECT id, username, email, name, role, last_login, created_at 
             FROM users ORDER BY name ASC"
        );
    }
    
    public static function find($id) {
        return Database::selectOne(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );
    }
    
    public static function findByEmail($email) {
        return Database::selectOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }
    
    public static function findByUsername($username) {
        return Database::selectOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
    }
    
    public static function create($data) {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return Database::insert('users', $data);
    }
    
    public static function update($id, $data) {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return Database::update('users', $data, 'id = ?', [$id]);
    }
    
    public static function delete($id) {
        return Database::delete('users', 'id = ?', [$id]);
    }
    
    public static function authenticate($login, $password) {
        // Busca por username ou email
        $user = Database::selectOne(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$login, $login]
        );
        
        if (!$user) {
            return null;
        }
        
        if (!password_verify($password, $user->password)) {
            return null;
        }
        
        // Atualiza último login
        Database::update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ], 'id = ?', [$user->id]);
        
        return $user;
    }
    
    public static function changePassword($id, $currentPassword, $newPassword) {
        $user = self::find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }
        
        if (!password_verify($currentPassword, $user->password)) {
            return ['success' => false, 'message' => 'Senha atual incorreta'];
        }
        
        self::update($id, ['password' => $newPassword]);
        
        return ['success' => true];
    }
    
    public static function generatePasswordReset($email) {
        $user = self::findByEmail($email);
        if (!$user) {
            return null;
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        Database::update('users', [
            'reset_token' => $token,
            'reset_expires' => $expires
        ], 'id = ?', [$user->id]);
        
        return $token;
    }
    
    public static function resetPassword($token, $newPassword) {
        $user = Database::selectOne(
            "SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Token inválido ou expirado'];
        }
        
        Database::update('users', [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_expires' => null
        ], 'id = ?', [$user->id]);
        
        return ['success' => true];
    }
    
    public static function count() {
        $result = Database::selectOne("SELECT COUNT(*) as total FROM users");
        return $result ? $result->total : 0;
    }
    
    public static function updateLastLogin($id) {
        return Database::update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ], 'id = ?', [$id]);
    }
}
