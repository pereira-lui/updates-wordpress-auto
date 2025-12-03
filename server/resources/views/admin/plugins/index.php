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
                            <button type="button" class="btn btn-sm btn-outline-info view-versions" data-id="<?= $plugin->id ?>" data-name="<?= htmlspecialchars($plugin->name) ?>" title="Ver versões">
                                <i class="bi bi-clock-history"></i>
                            </button>
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

<!-- Modal de versões -->
<div class="modal fade" id="versionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Versões: <span id="versionsPluginName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="versionsLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2 text-muted">Carregando versões...</p>
                </div>
                <div id="versionsContent" class="d-none">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        O sistema mantém automaticamente todas as versões anteriores dos plugins.
                    </div>
                    
                    <div id="versionsEmpty" class="text-center py-4 d-none">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <p class="text-muted">Nenhuma versão anterior encontrada.</p>
                    </div>
                    
                    <table id="versionsTable" class="table table-hover d-none">
                        <thead>
                            <tr>
                                <th>Versão</th>
                                <th>Arquivo</th>
                                <th>Data</th>
                                <th>Tamanho</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="versionsTableBody"></tbody>
                    </table>
                </div>
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

// View versions
const versionsModal = new bootstrap.Modal(document.getElementById('versionsModal'));
let currentVersionsPluginId = null;

document.querySelectorAll('.view-versions').forEach(btn => {
    btn.addEventListener('click', () => {
        currentVersionsPluginId = btn.dataset.id;
        document.getElementById('versionsPluginName').textContent = btn.dataset.name;
        loadVersions(btn.dataset.id);
        versionsModal.show();
    });
});

async function loadVersions(pluginId) {
    const loading = document.getElementById('versionsLoading');
    const content = document.getElementById('versionsContent');
    const table = document.getElementById('versionsTable');
    const tbody = document.getElementById('versionsTableBody');
    const empty = document.getElementById('versionsEmpty');
    
    loading.classList.remove('d-none');
    content.classList.add('d-none');
    
    try {
        const res = await fetch(`<?= url('/admin/plugins') ?>/${pluginId}/versions`);
        const data = await res.json();
        
        loading.classList.add('d-none');
        content.classList.remove('d-none');
        
        if (data.success) {
            const plugin = data.plugin;
            const versions = data.physical_versions || [];
            
            // Adiciona versão atual no topo
            const allVersions = [
                {
                    version: plugin.version,
                    filename: plugin.zip_file,
                    date: plugin.updated_at,
                    size: null,
                    is_current: true
                },
                ...versions.filter(v => v.version !== plugin.version).map(v => ({
                    ...v,
                    is_current: false
                }))
            ];
            
            if (allVersions.length === 0) {
                table.classList.add('d-none');
                empty.classList.remove('d-none');
            } else {
                empty.classList.add('d-none');
                table.classList.remove('d-none');
                
                tbody.innerHTML = allVersions.map(v => `
                    <tr>
                        <td>
                            <strong>v${escapeHtml(v.version)}</strong>
                            ${v.is_current ? '<span class="badge bg-primary ms-2">Atual</span>' : ''}
                        </td>
                        <td><code class="small">${escapeHtml(v.filename || '-')}</code></td>
                        <td>${v.date ? formatDate(v.date) : '-'}</td>
                        <td>${v.size ? formatBytes(v.size) : '-'}</td>
                        <td class="text-end">
                            ${!v.is_current ? `
                                <button type="button" class="btn btn-sm btn-outline-success restore-version" 
                                        data-version="${escapeHtml(v.version)}" title="Restaurar esta versão">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-version" 
                                        data-version="${escapeHtml(v.version)}" title="Excluir esta versão">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : '<span class="text-muted small">Versão ativa</span>'}
                        </td>
                    </tr>
                `).join('');
                
                // Bind events
                bindVersionActions();
            }
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${data.message || 'Erro ao carregar'}</td></tr>`;
            table.classList.remove('d-none');
        }
    } catch (e) {
        loading.classList.add('d-none');
        content.classList.remove('d-none');
        document.getElementById('versionsTableBody').innerHTML = '<tr><td colspan="5" class="text-danger">Erro de conexão</td></tr>';
        document.getElementById('versionsTable').classList.remove('d-none');
    }
}

function bindVersionActions() {
    // Restore version
    document.querySelectorAll('.restore-version').forEach(btn => {
        btn.addEventListener('click', async () => {
            const version = btn.dataset.version;
            if (!confirm(`Restaurar versão ${version}? A versão atual será movida para o histórico.`)) return;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            try {
                const res = await fetch(`<?= url('/admin/plugins') ?>/${currentVersionsPluginId}/restore-version`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `_token=<?= csrf_token() ?>&version=${encodeURIComponent(version)}`
                });
                const data = await res.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Falha ao restaurar'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Restaurar';
                }
            } catch (e) {
                alert('Erro de conexão');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Restaurar';
            }
        });
    });
    
    // Delete version
    document.querySelectorAll('.delete-version').forEach(btn => {
        btn.addEventListener('click', async () => {
            const version = btn.dataset.version;
            if (!confirm(`Excluir permanentemente a versão ${version}?`)) return;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            try {
                const res = await fetch(`<?= url('/admin/plugins') ?>/${currentVersionsPluginId}/delete-version`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `_token=<?= csrf_token() ?>&version=${encodeURIComponent(version)}`
                });
                const data = await res.json();
                
                if (data.success) {
                    btn.closest('tr').remove();
                } else {
                    alert('Erro: ' + (data.message || 'Falha ao excluir'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-trash"></i>';
                }
            } catch (e) {
                alert('Erro de conexão');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash"></i>';
            }
        });
    });
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}

function formatBytes(bytes) {
    if (!bytes) return '-';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
}
</script>
<?php $this->endSection(); ?>
