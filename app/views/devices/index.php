<div class="page-actions">
    <form method="get" action="/devices" class="search-form">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar por modelo, código Apple (A1502…), série ou cliente…">
        <select name="client_id" onchange="this.form.submit()">
            <option value="">Todos os clientes</option>
            <?php foreach ($clients as $client): ?>
                <option value="<?= $client['id'] ?>" <?= $filters['client_id'] == $client['id'] ? 'selected' : '' ?>>
                    <?= e($client['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost">Filtrar</button>
    </form>
    <a href="/devices/create" class="btn btn-primary">+ Novo equipamento</a>
</div>

<section class="card">
    <?php if (!$devices): ?>
        <p class="empty">Nenhum equipamento encontrado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Cliente</th><th>Categoria</th><th>Modelo</th><th>Código</th><th>Série</th><th>Ano</th><th class="actions-col"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><a href="/clients/view?id=<?= $device['client_id'] ?>" class="link"><?= e($device['client_name']) ?></a></td>
                        <td><?= e($device['category']) ?></td>
                        <td><?= e($device['model'] ?: '—') ?></td>
                        <td><?= $device['model_code'] ? '<span class="badge badge-code">' . e($device['model_code']) . '</span>' : '—' ?></td>
                        <td><?= e($device['serial_number'] ?: '—') ?></td>
                        <td><?= e($device['year'] ?: '—') ?></td>
                        <td class="actions-col">
                            <a href="/orders/create?client_id=<?= $device['client_id'] ?>&device_id=<?= $device['id'] ?>" class="btn btn-sm btn-ghost">+ OS</a>
                            <a href="/devices/edit?id=<?= $device['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                            <form method="post" action="/devices/delete" class="inline" data-confirm="Excluir este equipamento?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $device['id'] ?>">
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
