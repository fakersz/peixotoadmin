<div class="page-actions">
    <form method="get" action="/clients" class="search-form">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome, telefone, e-mail ou documento…">
        <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
    <a href="/clients/create" class="btn btn-primary">+ Novo cliente</a>
</div>

<section class="card">
    <?php if (!$clients): ?>
        <p class="empty">Nenhum cliente encontrado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Nome</th><th>Telefone</th><th>E-mail</th><th>Documento</th><th>Desde</th><th class="actions-col"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><a href="/clients/view?id=<?= $client['id'] ?>" class="link"><?= e($client['name']) ?></a></td>
                        <td><?= e($client['phone'] ?: '—') ?></td>
                        <td><?= e($client['email'] ?: '—') ?></td>
                        <td><?= e($client['document'] ?: '—') ?></td>
                        <td><?= br_date($client['created_at']) ?></td>
                        <td class="actions-col">
                            <a href="/clients/edit?id=<?= $client['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                            <form method="post" action="/clients/delete" class="inline" data-confirm="Excluir o cliente <?= e($client['name']) ?>? Os equipamentos dele também serão removidos.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $client['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger-ghost">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
