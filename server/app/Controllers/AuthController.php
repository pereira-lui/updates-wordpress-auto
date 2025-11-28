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
            redirect(url('/admin'));
        }
        
        return $this->view('auth/login');
    }
    
    /**
     * Processa o login
     */
    public function login() {
        $errors = $this->validate($_POST, [
            'login' => 'required',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash_set('error', 'Preencha todos os campos');
            redirect(url('/login'));
        }
        
        // Verifica CSRF
        if (!csrf_verify()) {
            flash_set('error', 'Token de segurança inválido');
            redirect(url('/login'));
        }
        
        $user = User::authenticate($_POST['login'], $_POST['password']);
        
        if (!$user) {
            flash_set('error', 'Credenciais inválidas');
            redirect(url('/login'));
        }
        
        // Define sessão
        session_set('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ]);
        
        // Atualiza último login
        User::updateLastLogin($user->id);
        
        flash_set('success', 'Bem-vindo, ' . $user->name . '!');
        
        // Redireciona para URL pretendida ou admin
        $intended = session('_intended') ?: url('/admin');
        session_forget('_intended');
        redirect($intended);
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        redirect(url('/login'));
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
        $errors = $this->validate($_POST, ['email' => 'required|email']);
        
        if (!empty($errors)) {
            flash_set('error', 'Informe um e-mail válido');
            redirect(url('/forgot-password'));
        }
        
        $token = User::generatePasswordReset($_POST['email']);
        
        // Sempre mostra mensagem de sucesso para não revelar se email existe
        flash_set('success', 'Se o e-mail existir em nossa base, você receberá um link de recuperação');
        redirect(url('/login'));
    }
    
    /**
     * Formulário de reset de senha
     */
    public function resetForm() {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            flash_set('error', 'Token inválido');
            redirect(url('/login'));
        }
        
        return $this->view('auth/reset', ['token' => $token]);
    }
    
    /**
     * Processa reset de senha
     */
    public function reset() {
        $errors = $this->validate($_POST, [
            'token' => 'required',
            'password' => 'required|min:6',
            'password_confirmation' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash_set('error', 'Preencha todos os campos corretamente');
            redirect(url('/reset-password?token=' . $_POST['token']));
        }
        
        if ($_POST['password'] !== $_POST['password_confirmation']) {
            flash_set('error', 'As senhas não conferem');
            redirect(url('/reset-password?token=' . $_POST['token']));
        }
        
        $result = User::resetPassword($_POST['token'], $_POST['password']);
        
        if (!$result['success']) {
            flash_set('error', $result['message']);
            redirect(url('/reset-password?token=' . $_POST['token']));
        }
        
        flash_set('success', 'Senha alterada com sucesso!');
        redirect(url('/login'));
    }
}
