<?php

namespace App\Middleware;

/**
 * Middleware de autenticação
 */
class Auth {
    
    public function handle() {
        if (!auth()) {
            redirect(url('login'));
            return false;
        }
        return true;
    }
}
