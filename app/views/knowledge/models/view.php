<?php
$assetGroups = [];
foreach ($assets as $asset) {
    $assetGroups[$asset['category']][] = $asset;
}
$labels = [
    'esquema' => 'Esquemas', 'boardview' => 'Boardviews', 'pdf' => 'PDFs', 'foto' => 'Fotos',
    'video' => 'Vídeos', 'datasheet' => 'Datasheets', 'firmware' => 'Firmware',
    'link' => 'Links', 'zip' => 'ZIPs', 'documento' => 'Documentos', 'arquivo' => 'Arquivos',
];
?>
<div class="page-actions">
    <a href="/knowledge/models" class="btn btn-ghost">← Modelos</a>
    <span class="spacer"></span>
    <a href="/knowledge/assets/create?model_id=<?= (int) $model['id'] ?>" class="btn btn-ghost">Enviar arquivo</a>
    <?php if (knowledge_can('model_manage')): ?><a href="/knowledge/models/edit?id=<?= (int) $model['id'] ?>" class="btn btn-primary">Editar modelo</a><?php endif; ?>
</div>
<section class="card sv-hero">
    <div>
        <span class="sv-cat"><?= e($model['manufacturer_name']) ?><?= $model['family'] ? ' · ' . e($model['family']) : '' ?></span>
        <h2 class="sv-title"><?= e($model['name']) ?></h2>
        <p class="hint"><?= e($model['description'] ?: 'Modelo cadastrado no Centro de Conhecimento.') ?></p>
        <div class="badge-row">
            <?php if ($model['release_year']): ?><span class="badge badge-code"><?= e((string) $model['release_year']) ?><?= $model['release_period'] ? ' · ' . e($model['release_period']) : '' ?></span><?php endif; ?>
            <?php if ($model['architecture']): ?><span class="badge badge-code"><?= e($model['architecture']) ?></span><?php endif; ?>
            <span class="badge badge-confidence-<?= e(str_replace('_', '-', $model['confidence_level'])) ?>"><?= e($model['confidence_level']) ?></span>
        </div>
    </div>
</section>
<section class="card">
    <div class="card-header"><h2>Identificadores</h2></div>
    <?php if (!$identifiers): ?>
        <p class="empty">Sem identificadores cadastrados (Model Identifier, EMC, A-number...).</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Tipo</th><th>Valor</th><th>Região</th><th>Confiança</th></tr></thead>
            <tbody>
            <?php foreach ($identifiers as $identifier): ?>
                <tr>
                    <td><?= e($identifier['identifier_type']) ?><?= $identifier['is_primary'] ? ' <span class="badge badge-code">principal</span>' : '' ?></td>
                    <td><span class="badge badge-code"><?= e($identifier['identifier_value']) ?></span></td>
                    <td><?= e($identifier['region'] ?: '-') ?></td>
                    <td><span class="badge badge-confidence-<?= e(str_replace('_', '-', $identifier['confidence_level'])) ?>"><?= e($identifier['confidence_level']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<section class="card">
    <div class="card-header"><h2>Variantes / configurações</h2></div>
    <?php if (!$variants): ?>
        <p class="empty">Nenhuma variante confirmada cadastrada.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Configuração</th><th>Processador</th><th>Memória</th><th>Armazenamento</th><th>Tipo</th></tr></thead>
            <tbody>
            <?php foreach ($variants as $variant): ?>
                <tr>
                    <td><?= e($variant['variant_name']) ?></td>
                    <td><?= e($variant['processor'] ?: '-') ?></td>
                    <td><?= e($variant['memory'] ?: '-') ?></td>
                    <td><?= e($variant['storage'] ?: '-') ?></td>
                    <td><?= e($variant['configuration_type']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Placas lógicas</h2></div>
        <?php foreach ($boards as $board): ?>
            <a class="knowledge-result" href="/knowledge/boards/view?id=<?= (int) $board['id'] ?>"><span class="badge badge-code"><?= e($board['full_code'] ?: $board['board_code']) ?></span><strong><?= e($board['description'] ?: '') ?></strong></a>
        <?php endforeach; ?>
        <?php if (!$boards): ?><p class="empty">Nenhuma placa vinculada.</p><?php endif; ?>
    </section>
    <section class="card">
        <div class="card-header"><h2>Compatibilidade macOS</h2></div>
        <?php foreach ($osCompatibility as $compat): ?>
            <div class="knowledge-result"><strong><?= e($compat['os_name'] . ' ' . $compat['os_version']) ?></strong><span><?= e($compat['compatibility_type']) ?><?= $compat['is_official'] ? '' : ' · não oficial' ?></span></div>
        <?php endforeach; ?>
        <?php if (!$osCompatibility): ?><p class="empty">Sem dados de compatibilidade cadastrados.</p><?php endif; ?>
    </section>
</div>
<?php if ($servicePrograms): ?>
<section class="card">
    <div class="card-header"><h2>Programas oficiais de serviço</h2></div>
    <?php foreach ($servicePrograms as $program): ?>
        <div class="knowledge-result"><strong><?= e($program['name']) ?></strong><span><?= e($program['symptom'] ?: '') ?></span></div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
<?php if ($sourceEvidence): ?>
<section class="card">
    <div class="card-header"><h2>Fontes e evidências</h2></div>
    <?php foreach ($sourceEvidence as $evidence): ?>
        <div class="knowledge-result">
            <strong><?= e($evidence['field_name']) ?>:</strong> <?= e($evidence['field_value'] ?: '') ?>
            <span><a href="<?= e($evidence['source_url']) ?>" target="_blank" rel="noopener"><?= e($evidence['source_title']) ?></a></span>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
<div class="stats-grid stats-grid-5">
    <a class="stat-card" href="/knowledge/articles?model_id=<?= (int) $model['id'] ?>"><div class="stat-label">Artigos</div><div class="stat-value"><?= count($articles) ?></div></a>
    <a class="stat-card" href="/knowledge/repairs?model_id=<?= (int) $model['id'] ?>"><div class="stat-label">Reparos</div><div class="stat-value"><?= count($repairs) ?></div></a>
    <a class="stat-card" href="/knowledge/schematics?q=<?= urlencode($model['code'] ?: $model['name']) ?>"><div class="stat-label">Esquemas</div><div class="stat-value"><?= count($schematics) ?></div></a>
    <div class="stat-card"><div class="stat-label">Arquivos</div><div class="stat-value"><?= count($assets) ?></div></div>
    <div class="stat-card"><div class="stat-label">Tags</div><div class="stat-value"><?= count(array_filter(array_map('trim', explode(',', (string) $model['tags'])))) ?></div></div>
</div>
<div class="panel-grid">
    <section class="card"><div class="card-header"><h2>Problemas frequentes</h2></div><div class="markdown-body"><?= md($model['frequent_issues'] ?: 'Sem problemas frequentes cadastrados.') ?></div></section>
    <section class="card"><div class="card-header"><h2>Diagnóstico e procedimentos</h2></div><div class="markdown-body"><?= md(trim(($model['diagnosis'] ?? '') . "\n\n" . ($model['procedures'] ?? '')) ?: 'Sem diagnóstico cadastrado.') ?></div></section>
</div>
<section class="card">
    <div class="card-header"><h2>Conteúdo relacionado</h2></div>
    <div class="knowledge-columns">
        <div>
            <h3 class="section-mini-title">Artigos</h3>
            <?php foreach (array_slice($articles, 0, 8) as $article): ?><a class="knowledge-result" href="/knowledge/articles/view?id=<?= (int) $article['id'] ?>"><span class="badge badge-code">artigo</span><strong><?= e($article['title']) ?></strong><span><?= e($article['summary'] ?: '') ?></span></a><?php endforeach; ?>
            <?php if (!$articles): ?><p class="empty">Sem artigos.</p><?php endif; ?>
        </div>
        <div>
            <h3 class="section-mini-title">Reparos</h3>
            <?php foreach (array_slice($repairs, 0, 8) as $repair): ?><a class="knowledge-result" href="/knowledge/repairs/view?id=<?= (int) $repair['id'] ?>"><span class="badge badge-code">reparo</span><strong><?= e($repair['title']) ?></strong><span><?= e($repair['problem'] ?: '') ?></span></a><?php endforeach; ?>
            <?php if (!$repairs): ?><p class="empty">Sem reparos.</p><?php endif; ?>
        </div>
    </div>
</section>
<section class="card">
    <div class="card-header"><h2>Arquivos por tipo</h2></div>
    <div class="knowledge-columns">
        <?php foreach ($labels as $category => $label): ?>
            <div class="asset-group">
                <h3 class="section-mini-title"><?= e($label) ?></h3>
                <?php foreach (array_slice($assetGroups[$category] ?? [], 0, 6) as $asset): ?>
                    <a class="knowledge-result" href="/knowledge/assets/view?id=<?= (int) $asset['id'] ?>"><span class="badge badge-code"><?= e($asset['category']) ?></span><strong><?= e($asset['title']) ?></strong><span><?= e($asset['description'] ?: $asset['original_name'] ?: '') ?></span></a>
                <?php endforeach; ?>
                <?php if (empty($assetGroups[$category])): ?><p class="empty">Sem registros.</p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<section class="card">
    <div class="card-header"><h2>Tags relacionadas</h2></div>
    <div class="badge-row">
        <?php foreach (array_filter(array_map('trim', explode(',', (string) $model['tags']))) as $tag): ?>
            <a class="chip" href="/knowledge?q=<?= urlencode($tag) ?>"><?= e($tag) ?></a>
        <?php endforeach; ?>
        <?php if (!$model['tags']): ?><p class="empty">Sem tags cadastradas.</p><?php endif; ?>
    </div>
</section>
