<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * Controller de Autenticação
 */
class AuthController extends Controller {
    
    /**
     * Exibe o formulário de login
     */
    public function loginForm() {
        // Se já está logado, redireciona
        if (auth()) {
            redirect('/admin');
        }
        
        return $this->view('auth/login');
    }
    
    /**
     * Processa o login
     */
    public function login() {
        $errors = $this->validate([
            'login' => 'required',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash('error', 'Preencha todos os campos');
            redirect('/login');
        }
        
        // Verifica CSRF
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/login');
        }
        
        $user = User::authenticate($_POST['login'], $_POST['password']);
        
        if (!$user) {
            flash('error', 'Credenciais inválidas');
            redirect('/login');
        }
        
        // Define sessão
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_role'] = $user->role;
        
        flash('success', 'Bem-vindo, ' . $user->name . '!');
        redirect('/admin');
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        redirect('/login');
    }
    
    /**
     * Formulário de recuperação de senha
     */
    public function forgotForm() {
        return $this->view('auth/forgot');
    }
    
    /**
     * Processa recuperação de senha
     */
    public function forgot() {
        $errors = $this->validate(['email' => 'required|email']);
        
        if (!empty($errors)) {
            flash('error', 'Informe um e-mail válido');
            redirect('/forgot-password');
        }
        
        $token = User::generatePasswordReset($_POST['email']);
        
        // Sempre mostra mensagem de sucesso para não revelar se email existe
        flash('success', 'Se o e-mail existir em nossa base, você receberá um link de recuperação');
        redirect('/login');
        
        // TODO: Enviar e-mail com link de recuperação
        // mail($_POST['email'], 'Recuperação de senha', url('/reset-password?token=' . $token));
    }
    
    /**
     * Formulário de reset de senha
     */
    public function resetForm() {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            flash('error', 'Token inválido');
            redirect('/login');
        }
        
        return $this->view('auth/reset', ['token' => $token]);
    }
    
    /**
     * Processa reset de senha
     */
    public function reset() {
        $errors = $this->validate([
            'token' => 'required',
            'password' => 'required|min:6',
            'password_confirmation' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash('error', 'Preencha todos os campos corretamente');
            redirect('/reset-password?token=' . $_POST['token']);
        }
        
        if ($_POST['password'] !== $_POST['password_confirmation']) {
            flash('error', 'As senhas não conferem');
            redirect('/reset-password?token=' . $_POST['token']);
        }
        
        $result = User::resetPassword($_POST['token'], $_POST['password']);
        
        if (!$result['success']) {
            flash('error', $result['message']);
            redirect('/reset-password?token=' . $_POST['token']);
        }
        
        flash('success', 'Senha alterada com sucesso!');
        redirect('/login');
    }
}
