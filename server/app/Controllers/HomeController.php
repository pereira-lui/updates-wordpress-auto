<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controller da página inicial
 */
class HomeController extends Controller {
    
    /**
     * Página inicial - redireciona para login ou admin
     */
    public function index() {
        if (auth()) {
            redirect(url('/admin'));
        }
        redirect(url('/login'));
    }
}
