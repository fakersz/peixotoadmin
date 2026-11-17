<div class="page-actions">
    <a href="/knowledge/repairs" class="btn btn-ghost">← Reparos</a>
    <span class="spacer"></span>
    <a href="/knowledge/assets/create?category=foto&repair_id=<?= (int) $repair['id'] ?>" class="btn btn-ghost">Anexar arquivo</a>
    <?php if (knowledge_can('article_create')): ?>
        <form method="post" action="/knowledge/repairs/convert-article" class="inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $repair['id'] ?>">
            <button type="submit" class="btn btn-primary">Converter em artigo</button>
        </form>
    <?php endif; ?>
    <?php if (knowledge_can('repair_edit', $repair)): ?><a href="/knowledge/repairs/edit?id=<?= (int) $repair['id'] ?>" class="btn btn-ghost">Editar</a><?php endif; ?>
    <?php if (knowledge_can('repair_delete', $repair)): ?>
        <form method="post" action="/knowledge/repairs/delete" class="inline" data-confirm="Arquivar este reparo?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $repair['id'] ?>">
            <button type="submit" class="btn btn-danger-ghost">Excluir</button>
        </form>
    <?php endif; ?>
</div>

<section class="card">
    <div class="card-header">
        <h2><?= e($repair['title']) ?></h2>
        <div class="badge-row">
            <span class="badge <?= $repair['status'] === 'publicado' ? 'badge-in' : 'badge-low' ?>"><?= e($repair['status']) ?></span>
            <span class="badge badge-code"><?= e($repair['result'] ?: 'resultado') ?></span>
        </div>
    </div>
    <dl class="detail-grid">
        <div><dt>Modelo</dt><dd><?= e(trim(($repair['manufacturer_name'] ?? '') . ' ' . ($repair['model_code'] ?: $repair['model_name'] ?: '')) ?: '-') ?></dd></div>
        <div><dt>Cliente</dt><dd><?= e($repair['client_name'] ?: '-') ?></dd></div>
        <div><dt>OS</dt><dd><?= $repair['service_order_id'] ? '<a class="link" href="/orders/view?id=' . (int) $repair['service_order_id'] . '">#' . (int) $repair['service_order_id'] . '</a>' : '-' ?></dd></div>
        <div><dt>Equipamento</dt><dd><?= e(trim(($repair['device_category'] ?? '') . ' ' . ($repair['device_model'] ?? '')) ?: '-') ?></dd></div>
        <div><dt>Tempo</dt><dd><?= $repair['time_minutes'] ? e($repair['time_minutes'] . ' min') : '-' ?></dd></div>
        <div><dt>Custo</dt><dd><?= money($repair['cost'] ?? 0) ?></dd></div>
        <div><dt>Data</dt><dd><?= br_date($repair['repair_date'] ?? $repair['created_at']) ?></dd></div>
        <div><dt>Tags</dt><dd><?= e($repair['tags'] ?: '-') ?></dd></div>
        <div class="span-all"><dt>Problema relatado</dt><dd><?= $repair['problem'] ? nl2br(e($repair['problem'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Sintomas</dt><dd><?= $repair['symptoms'] ? nl2br(e($repair['symptoms'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Diagnóstico</dt><dd><?= $repair['diagnosis'] ? nl2br(e($repair['diagnosis'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Testes realizados</dt><dd><?= $repair['tests_performed'] ? nl2br(e($repair['tests_performed'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Solução</dt><dd><?= $repair['solution'] ? nl2br(e($repair['solution'])) : '-' ?></dd></div>
        <div><dt>Peças</dt><dd><?= $repair['parts'] ? nl2br(e($repair['parts'])) : '-' ?></dd></div>
        <div><dt>Ferramentas</dt><dd><?= $repair['tools'] ? nl2br(e($repair['tools'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Observações</dt><dd><?= $repair['observations'] ? nl2br(e($repair['observations'])) : '-' ?></dd></div>
    </dl>
</section>
