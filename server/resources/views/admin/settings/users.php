<?php $this->layout('layouts/admin', ['title' => 'Usuários']); ?>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="list-group">
            <a href="<?= url('/admin/settings') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-gear"></i> Geral
            </a>
            <a href="<?= url('/admin/settings/profile') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-person"></i> Meu Perfil
            </a>
            <a href="<?= url('/admin/settings/users') ?>" class="list-group-item list-group-item-action active">
                <i class="bi bi-people"></i> Usuários
            </a>
            <a href="<?= url('/admin/settings/logs') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-journal-text"></i> Logs
            </a>
        </div>
    </div>
    
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Usuários Administrativos</span>
                <a href="<?= url('/admin/settings/users/create') ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Novo Usuário
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Username</th>
                                <th>E-mail</th>
                                <th>Último Login</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user->name) ?></td>
                                    <td><code><?= htmlspecialchars($user->username) ?></code></td>
                                    <td><?= htmlspecialchars($user->email) ?></td>
                                    <td>
                                        <?= $user->last_login ? date('d/m/Y H:i', strtotime($user->last_login)) : 'Nunca' ?>
                                    </td>
                                    <td>
                                        <?php if ($user->id != auth()->id): ?>
                                            <form method="POST" action="<?= url('/admin/settings/users/' . $user->id) ?>" class="d-inline" onsubmit="return confirm('Excluir este usuário?')">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-info">Você</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
