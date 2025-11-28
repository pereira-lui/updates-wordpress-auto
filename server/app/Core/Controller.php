<?php

namespace App\Core;

/**
 * Controller base
 */
class Controller {
    
    protected $viewData = [];
    protected $layoutName = null;
    protected $layoutData = [];
    protected $sections = [];
    protected $currentSection = null;
    
    /**
     * Renderiza uma view
     */
    protected function view($name, $data = []) {
        $this->viewData = $data;
        $this->layoutName = null;
        $this->layoutData = [];
        $this->sections = [];
        
        extract($data);
        
        $viewFile = VIEWS_PATH . '/' . str_replace('.', '/', $name) . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View não encontrada: {$name}");
        }
        
        // Captura o conteúdo da view
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // Se foi definido um layout, renderiza dentro dele
        if ($this->layoutName) {
            $layoutFile = VIEWS_PATH . '/' . str_replace('.', '/', $this->layoutName) . '.php';
            
            if (!file_exists($layoutFile)) {
                throw new \Exception("Layout não encontrado: {$this->layoutName}");
            }
            
            // Mescla dados da view com dados do layout
            $allData = array_merge($this->viewData, $this->layoutData, [
                'content' => $content,
                'sections' => $this->sections
            ]);
            
            extract($allData);
            include $layoutFile;
        } else {
            echo $content;
        }
    }
    
    /**
     * Define o layout a ser usado pela view
     * Chamado pela view: $this->layout('layouts/admin', ['title' => 'Título'])
     */
    protected function layout($name, $data = []) {
        $this->layoutName = $name;
        $this->layoutData = $data;
    }
    
    /**
     * Inicia uma seção nomeada
     * Chamado pela view: $this->section('scripts')
     */
    protected function section($name) {
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * Finaliza a seção atual
     * Chamado pela view: $this->endSection()
     */
    protected function endSection() {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }
    
    /**
     * Renderiza uma seção no layout
     */
    protected function renderSection($name, $default = '') {
        return $this->sections[$name] ?? $default;
    }
    
    /**
     * Retorna JSON
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Valida dados
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $fieldRules);
            
            foreach ($rulesList as $rule) {
                $params = [];
                
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramStr) = explode(':', $rule);
                    $params = explode(',', $paramStr);
                }
                
                $error = $this->validateRule($field, $value, $rule, $params);
                
                if ($error) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }
        
        return $errors;
    }
    
    private function validateRule($field, $value, $rule, $params) {
        $fieldName = ucfirst(str_replace('_', ' ', $field));
        
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    return "{$fieldName} é obrigatório";
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$fieldName} deve ser um e-mail válido";
                }
                break;
                
            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$fieldName} deve ser uma URL válida";
                }
                break;
                
            case 'min':
                if (strlen($value) < $params[0]) {
                    return "{$fieldName} deve ter pelo menos {$params[0]} caracteres";
                }
                break;
                
            case 'max':
                if (strlen($value) > $params[0]) {
                    return "{$fieldName} deve ter no máximo {$params[0]} caracteres";
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return "{$fieldName} deve ser numérico";
                }
                break;
        }
        
        return null;
    }
}
