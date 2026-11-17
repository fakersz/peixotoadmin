<?php $activeKnowledgeSection = 'dashboard'; ?>
<div class="page-actions">
    <form method="get" action="/knowledge" class="search-form search-form-wide">
        <input type="search" name="q" value="<?= e($query) ?>" placeholder="Pesquisar modelo, fabricante, sintoma, placa, peça, arquivo, tag...">
        <select name="type">
            <option value="">Tipo</option>
            <?php foreach (['article' => 'Artigo', 'repair' => 'Reparo', 'schematic' => 'Esquema', 'model' => 'Modelo', 'manufacturer' => 'Fabricante', 'asset' => 'Arquivo'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($filters['type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
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
        <input type="month" name="period" value="<?= e($filters['period'] ?? '') ?>">
        <label class="check-inline"><input type="checkbox" name="favorite" value="1" <?= !empty($filters['favorite']) ? 'checked' : '' ?>> Favoritos</label>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
    <?php if (knowledge_can('article_create')): ?><a href="/knowledge/articles/create" class="btn btn-ghost">Novo artigo</a><?php endif; ?>
</div>

<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>

<div class="stats-grid">
    <a class="stat-card" href="/knowledge/articles"><div class="stat-label">Artigos</div><div class="stat-value stat-accent"><?= (int) $stats['articles'] ?></div><div class="stat-hint">procedimentos e notas</div></a>
    <a class="stat-card" href="/knowledge/repairs"><div class="stat-label">Reparos</div><div class="stat-value stat-ok"><?= (int) $stats['repairs'] ?></div><div class="stat-hint">casos técnicos</div></a>
    <a class="stat-card" href="/knowledge/schematics"><div class="stat-label">Esquemas</div><div class="stat-value stat-warn"><?= (int) $stats['schematics'] ?></div><div class="stat-hint">catálogo migrado</div></a>
    <a class="stat-card" href="/knowledge/models"><div class="stat-label">Modelos</div><div class="stat-value"><?= (int) $stats['models'] ?></div><div class="stat-hint"><?= (int) $stats['manufacturers'] ?> fabricantes</div></a>
</div>

<?php if ($query): ?>
    <section class="card">
        <div class="card-header"><h2>Resultados para “<?= e($query) ?>”</h2><span class="bcol-count"><?= count($results) ?></span></div>
        <?php if (!$results): ?>
            <p class="empty">Nada encontrado. Tente buscar pelo código da placa, modelo comercial, sintoma principal ou remova alguns filtros.</p>
            <div class="badge-row">
                <a class="chip" href="/knowledge?q=<?= urlencode($query) ?>&type=schematic">Buscar em esquemas</a>
                <a class="chip" href="/knowledge/articles/create">Criar artigo rascunho</a>
                <a class="chip" href="/knowledge/repairs/create">Registrar reparo</a>
            </div>
        <?php else: ?>
            <div class="knowledge-result-list">
                <?php foreach ($results as $result): ?>
                    <a href="<?= e($result['url']) ?>" class="knowledge-result">
                        <span class="badge badge-code"><?= e($result['type']) ?></span>
                        <strong><?= highlight_term($result['title'], $query) ?></strong>
                        <span><?= highlight_term(mb_substr((string) ($result['snippet'] ?? ''), 0, 220), $query) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Últimos adicionados</h2></div>
        <?php if (!$latest): ?>
            <p class="empty">Sem registros recentes.</p>
        <?php else: ?>
            <div class="knowledge-result-list">
                <?php foreach ($latest as $item): ?>
                    <a class="knowledge-result" href="<?= e($item['url']) ?>">
                        <span class="badge badge-code"><?= e($item['kind']) ?></span>
                        <strong><?= e($item['title']) ?></strong>
                        <span><?= e(br_date($item['created_at'], true)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-header"><h2>Pesquisas</h2></div>
        <div class="knowledge-columns">
            <div>
                <h3 class="section-mini-title">Recentes</h3>
                <div class="badge-row">
                    <?php foreach ($recentSearches as $search): ?><a class="chip" href="/knowledge?q=<?= urlencode($search['query']) ?>"><?= e($search['query']) ?></a><?php endforeach; ?>
                    <?php if (!$recentSearches): ?><p class="empty">Sem pesquisas recentes.</p><?php endif; ?>
                </div>
            </div>
            <div>
                <h3 class="section-mini-title">Frequentes</h3>
                <div class="badge-row">
                    <?php foreach ($frequentSearches as $search): ?><a class="chip" href="/knowledge?q=<?= urlencode($search['query']) ?>"><?= e($search['query']) ?> (<?= (int) $search['total'] ?>)</a><?php endforeach; ?>
                    <?php if (!$frequentSearches): ?><p class="empty">Sem volume suficiente.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>
