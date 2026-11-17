<div class="page-actions">
    <a href="/knowledge/boards" class="btn btn-ghost">← Placas</a>
</div>
<section class="card sv-hero">
    <div>
        <span class="sv-cat">Placa lógica</span>
        <h2 class="sv-title"><?= e($board['full_code'] ?: $board['board_code']) ?></h2>
        <p class="hint"><?= e($board['description'] ?: 'Sem descrição cadastrada.') ?></p>
        <div class="badge-row">
            <span class="badge badge-confidence-<?= e(str_replace('_', '-', $board['confidence_level'])) ?>"><?= e($board['confidence_level']) ?></span>
            <span class="badge badge-confidence-<?= e(str_replace('_', '-', $board['verification_status'])) ?>"><?= e($board['verification_status']) ?></span>
        </div>
    </div>
</section>
<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Ficha técnica</h2></div>
        <table class="kv-table">
            <tr><th>Código base</th><td><?= e($board['board_code']) ?></td></tr>
            <tr><th>Revisão</th><td><?= e($board['revision'] ?: '-') ?></td></tr>
            <tr><th>Board ID</th><td><?= e($board['board_id'] ?: '-') ?></td></tr>
            <tr><th>Ano</th><td><?= e((string) ($board['year'] ?: '-')) ?></td></tr>
            <tr><th>Arquitetura</th><td><?= e($board['architecture'] ?: '-') ?></td></tr>
            <tr><th>Processador</th><td><?= e($board['processor'] ?: '-') ?></td></tr>
            <tr><th>GPU</th><td><?= e($board['gpu'] ?: '-') ?></td></tr>
            <tr><th>Memória</th><td><?= e($board['memory'] ?: '-') ?></td></tr>
        </table>
    </section>
    <section class="card">
        <div class="card-header"><h2>Modelos compatíveis</h2></div>
        <?php foreach ($models as $model): ?>
            <a class="knowledge-result" href="/knowledge/models/view?id=<?= (int) $model['id'] ?>"><strong><?= e($model['manufacturer_name'] . ' ' . ($model['code'] ?: $model['name'])) ?></strong></a>
        <?php endforeach; ?>
        <?php if (!$models): ?><p class="empty">Nenhum modelo vinculado ainda.</p><?php endif; ?>
    </section>
</div>
<?php if ($board['notes']): ?>
<section class="card"><div class="card-header"><h2>Observações</h2></div><div class="markdown-body"><?= md($board['notes']) ?></div></section>
<?php endif; ?>
<section class="card">
    <div class="card-header"><h2>Fontes e evidências</h2></div>
    <?php foreach ($sourceEvidence as $evidence): ?>
        <div class="knowledge-result">
            <strong><?= e($evidence['field_name']) ?>:</strong> <?= e($evidence['field_value'] ?: '') ?>
            <span><a href="<?= e($evidence['source_url']) ?>" target="_blank" rel="noopener"><?= e($evidence['source_title']) ?></a></span>
        </div>
    <?php endforeach; ?>
    <?php if (!$sourceEvidence): ?><p class="empty">Sem evidências registradas.</p><?php endif; ?>
</section>
