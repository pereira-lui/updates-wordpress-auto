<?php $this->layout('layouts/admin', ['title' => 'Novo Plugin']); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-puzzle"></i> Cadastrar Novo Plugin
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/plugins') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome do Plugin *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Versão *</label>
                            <input type="text" name="version" class="form-control" value="1.0.0" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" class="form-control" placeholder="meu-plugin" required>
                            <small class="text-muted">Identificador único (sem espaços)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Arquivo ZIP</label>
                            <input type="file" name="zip_file" class="form-control" accept=".zip">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Changelog</label>
                            <textarea name="changelog" class="form-control" rows="3" placeholder="= 1.0.0 =
* Versão inicial"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Autor</label>
                            <input type="text" name="author" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Site do Autor</label>
                            <input type="url" name="author_uri" class="form-control" placeholder="https://...">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Site do Plugin</label>
                            <input type="url" name="plugin_uri" class="form-control" placeholder="https://...">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Requer WP</label>
                            <input type="text" name="requires_wp" class="form-control" value="5.0">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Testado até</label>
                            <input type="text" name="tested_wp" class="form-control" value="6.4">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Requer PHP</label>
                            <input type="text" name="requires_php" class="form-control" value="7.4">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                <label class="form-check-label" for="isActive">Plugin ativo</label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Cadastrar Plugin
                        </button>
                        <a href="<?= url('/admin/plugins') ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
