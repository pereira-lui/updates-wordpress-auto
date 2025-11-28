<?php $this->layout('layouts/admin', ['title' => 'Plugins']); ?>

<div class="row g-4">
    <!-- Área de Upload -->
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-body">
                <div id="dropzone" class="dropzone-area text-center p-5 border-2 border-dashed rounded-3" style="border: 2px dashed #dee2e6; cursor: pointer; transition: all 0.3s;">
                    <i class="bi bi-cloud-upload fs-1 text-primary mb-3 d-block"></i>
                    <h5>Arraste o arquivo ZIP do plugin aqui</h5>
                    <p class="text-muted mb-0">ou clique para selecionar</p>
                    <input type="file" id="pluginZip" accept=".zip" class="d-none">
                </div>
                <div id="uploadProgress" class="mt-3 d-none">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <p class="text-center mt-2 text-muted" id="uploadStatus">Enviando...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Plugins -->
    <?php if (empty($plugins)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-puzzle fs-1 text-muted mb-3 d-block"></i>
                    <h5>Nenhum plugin cadastrado</h5>
                    <p class="text-muted">Arraste um arquivo ZIP acima para adicionar seu primeiro plugin.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($plugins as $plugin): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1"><?= htmlspecialchars($plugin->name) ?></h5>
                                <code class="text-muted small"><?= htmlspecialchars($plugin->slug) ?></code>
                            </div>
                            <span class="badge <?= $plugin->is_active ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $plugin->is_active ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                        
                        <?php if ($plugin->description): ?>
                        <p class="text-muted small mb-3">
                            <?= htmlspecialchars(substr($plugin->description, 0, 100)) ?>
                            <?= strlen($plugin->description) > 100 ? '...' : '' ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="d-flex flex-wrap gap-2 text-muted small mb-2">
                            <span><i class="bi bi-tag"></i> v<?= htmlspecialchars($plugin->version) ?></span>
                            <span><i class="bi bi-download"></i> <?= number_format($plugin->downloads ?? 0) ?></span>
                            <?php if ($plugin->author): ?>
                            <span><i class="bi bi-person"></i> <?= htmlspecialchars($plugin->author) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($plugin->requires_wp || $plugin->requires_php): ?>
                        <div class="text-muted small">
                            <?php if ($plugin->requires_wp): ?>
                            <span class="me-2">WP: <?= htmlspecialchars($plugin->requires_wp) ?>+</span>
                            <?php endif; ?>
                            <?php if ($plugin->requires_php): ?>
                            <span>PHP: <?= htmlspecialchars($plugin->requires_php) ?>+</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$plugin->zip_file): ?>
                            <div class="alert alert-warning small py-2 mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle"></i> Arquivo ZIP não encontrado
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill update-plugin" data-id="<?= $plugin->id ?>" data-slug="<?= htmlspecialchars($plugin->slug) ?>">
                                <i class="bi bi-cloud-upload"></i> Atualizar
                            </button>
                            <button type="button" class="btn btn-sm <?= $plugin->is_active ? 'btn-outline-warning' : 'btn-outline-success' ?> toggle-plugin" data-id="<?= $plugin->id ?>" title="<?= $plugin->is_active ? 'Desativar' : 'Ativar' ?>">
                                <i class="bi bi-<?= $plugin->is_active ? 'pause' : 'play' ?>"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-plugin" data-id="<?= $plugin->id ?>" data-name="<?= htmlspecialchars($plugin->name) ?>" title="Excluir">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o plugin <strong id="deletePluginName"></strong>?</p>
                <p class="text-danger small">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Input oculto para atualização -->
<input type="file" id="updateZip" accept=".zip" class="d-none">

<?php $this->section('scripts'); ?>
<script>
const dropzone = document.getElementById('dropzone');
const pluginZip = document.getElementById('pluginZip');
const uploadProgress = document.getElementById('uploadProgress');
const progressBar = uploadProgress.querySelector('.progress-bar');
const uploadStatus = document.getElementById('uploadStatus');
const updateZip = document.getElementById('updateZip');
let currentUpdateId = null;

// Drag and drop
dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.style.borderColor = '#0d6efd';
    dropzone.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
});

dropzone.addEventListener('dragleave', () => {
    dropzone.style.borderColor = '#dee2e6';
    dropzone.style.backgroundColor = 'transparent';
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.style.borderColor = '#dee2e6';
    dropzone.style.backgroundColor = 'transparent';
    
    const file = e.dataTransfer.files[0];
    if (file && file.name.endsWith('.zip')) {
        uploadPlugin(file);
    } else {
        alert('Por favor, envie um arquivo .zip');
    }
});

dropzone.addEventListener('click', () => pluginZip.click());

pluginZip.addEventListener('change', (e) => {
    if (e.target.files[0]) {
        uploadPlugin(e.target.files[0]);
    }
});

// Atualizar plugin existente
document.querySelectorAll('.update-plugin').forEach(btn => {
    btn.addEventListener('click', () => {
        currentUpdateId = btn.dataset.id;
        updateZip.click();
    });
});

updateZip.addEventListener('change', (e) => {
    if (e.target.files[0] && currentUpdateId) {
        uploadPlugin(e.target.files[0], currentUpdateId);
    }
});

function uploadPlugin(file, updateId = null) {
    const formData = new FormData();
    formData.append('zip_file', file);
    formData.append('_token', '<?= csrf_token() ?>');
    
    if (updateId) {
        formData.append('update_id', updateId);
    }
    
    uploadProgress.classList.remove('d-none');
    dropzone.style.display = 'none';
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    uploadStatus.textContent = 'Enviando arquivo...';
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
            
            if (percent === 100) {
                uploadStatus.textContent = 'Processando plugin...';
            }
        }
    });
    
    xhr.addEventListener('load', () => {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                uploadStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + response.message + '</span>';
                setTimeout(() => location.reload(), 1500);
            } else {
                uploadStatus.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + response.message + '</span>';
                setTimeout(() => {
                    uploadProgress.classList.add('d-none');
                    dropzone.style.display = 'block';
                }, 3000);
            }
        } catch (e) {
            uploadStatus.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Erro ao processar resposta</span>';
        }
    });
    
    xhr.addEventListener('error', () => {
        uploadStatus.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Erro de conexão</span>';
    });
    
    xhr.open('POST', '<?= url('/admin/plugins/upload') ?>');
    xhr.send(formData);
}

// Toggle status
document.querySelectorAll('.toggle-plugin').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const res = await fetch(`<?= url('/admin/plugins') ?>/${id}/toggle`, { 
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=<?= csrf_token() ?>'
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    });
});

// Delete plugin
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
document.querySelectorAll('.delete-plugin').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deletePluginName').textContent = btn.dataset.name;
        document.getElementById('deleteForm').action = `<?= url('/admin/plugins') ?>/${btn.dataset.id}/delete`;
        deleteModal.show();
    });
});
</script>
<?php $this->endSection(); ?>
