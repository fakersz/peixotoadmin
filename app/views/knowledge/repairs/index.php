<?php $activeKnowledgeSection = 'repairs'; ?>
<div class="page-actions">
    <form method="get" action="/knowledge/repairs" class="search-form search-form-wide">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar por problema, sintoma, diagnóstico, solução, peça, modelo...">
        <select name="manufacturer_id">
            <option value="">Fabricante</option>
            <?php foreach ($manufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['id'] ?>" <?= (int) ($filters['manufacturer_id'] ?? 0) === (int) $manufacturer['id'] ? 'selected' : '' ?>><?= e($manufacturer['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Status</option>
            <?php foreach (KNOWLEDGE_ARTICLE_STATUSES as $status): ?>
                <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">Filtrar</button>
    </form>
    <?php if (knowledge_can('repair_create')): ?><a href="/knowledge/repairs/create" class="btn btn-primary">Novo reparo</a><?php endif; ?>
</div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>

<section class="card">
    <?php if (!$repairs): ?>
        <p class="empty">Nenhum reparo registrado. Use uma OS entregue ou cadastre um caso técnico manualmente.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Reparo</th><th>Modelo</th><th>Cliente</th><th>Resultado</th><th>Tempo</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($repairs as $repair): ?>
                    <tr>
                        <td><a href="/knowledge/repairs/view?id=<?= (int) $repair['id'] ?>" class="link link-plain"><?= e($repair['title']) ?></a></td>
                        <td><?= e(trim(($repair['manufacturer_name'] ?? '') . ' ' . ($repair['model_code'] ?: $repair['model_name'] ?: '')) ?: '-') ?></td>
                        <td><?= e($repair['client_name'] ?: '-') ?></td>
                        <td><?= e($repair['result'] ?: '-') ?></td>
                        <td><?= $repair['time_minutes'] ? e($repair['time_minutes'] . ' min') : '-' ?></td>
                        <td><span class="badge <?= $repair['status'] === 'publicado' ? 'badge-in' : 'badge-low' ?>"><?= e($repair['status']) ?></span></td>
                        <td class="actions-col"><?php if (knowledge_can('repair_edit', $repair)): ?><a href="/knowledge/repairs/edit?id=<?= (int) $repair['id'] ?>" class="btn btn-sm btn-ghost">Editar</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
