<?php $schematicsBase = $schematicsBase ?? '/schematics'; ?>
<div class="page-actions">
    <a href="<?= e($schematicsBase) ?>" class="btn btn-ghost">← Centro de Conhecimento</a>
    <span class="spacer"></span>
    <a href="<?= e($schematicsBase) ?>/edit?id=<?= $schematic['id'] ?>" class="btn btn-ghost">Editar</a>
    <form method="post" action="<?= e($schematicsBase) ?>/delete" class="inline" data-confirm="Excluir este esquema? O PDF também será apagado.">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $schematic['id'] ?>">
        <button type="submit" class="btn btn-danger-ghost">Excluir</button>
    </form>
</div>

<!-- Cabeçalho da placa -->
<section class="card sv-hero">
    <div class="sv-hero-main">
        <?php if ($schematic['device_category']): ?>
            <span class="sv-cat"><?= e($schematic['device_category']) ?></span>
        <?php endif; ?>
        <h2 class="sv-title"><?= e($schematic['title']) ?></h2>
        <div class="sv-badges">
            <?php if ($schematic['model_code']): ?>
                <span class="badge badge-code"><?= e($schematic['model_code']) ?></span>
            <?php endif; ?>
            <?php if ($schematic['board_code']): ?>
                <span class="badge badge-board"><?= e($schematic['board_code']) ?></span>
            <?php else: ?>
                <span class="board-missing" title="Conferir a serigrafia 820-xxxxx na placa">board a confirmar</span>
            <?php endif; ?>
            <?php if ($schematic['year']): ?>
                <span class="badge sv-year"><?= e($schematic['year']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="sv-hero-actions">
        <?php if ($schematic['file_path']): ?>
            <?php
            $ext      = schematic_file_ext($schematic['file_path']);
            $isInline = schematic_file_inline_type($ext) !== null;
            $label    = match (true) {
                $ext === 'pdf' => 'Abrir esquema PDF',
                $isInline      => 'Abrir arquivo (' . strtoupper((string) $ext) . ')',
                default        => 'Baixar arquivo (' . strtoupper((string) $ext) . ')',
            };
            ?>
            <a href="<?= e($schematicsBase) ?>/file?id=<?= $schematic['id'] ?>" <?= $isInline ? 'target="_blank"' : '' ?> class="btn btn-primary">
                <?php if ($isInline): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                <?php endif; ?>
                <?= e($label) ?>
            </a>
            <form method="post" action="<?= e($schematicsBase) ?>/delete-file" class="inline"
                  data-confirm="Remover o arquivo anexado? O registro do catálogo será mantido.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $schematic['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger-ghost">Remover arquivo</button>
            </form>
        <?php else: ?>
            <a href="<?= e($schematicsBase) ?>/edit?id=<?= $schematic['id'] ?>" class="sv-nopdf" title="Editar e anexar o PDF">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15h6"/></svg>
                PDF ainda não anexado
            </a>
        <?php endif; ?>
        <?php if ($schematic['external_link']): ?>
            <a href="<?= e($schematic['external_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">🔗 Link externo</a>
        <?php endif; ?>
    </div>
</section>

<!-- Informações + descrição técnica lado a lado -->
<div class="sv-grid">
    <section class="card">
        <div class="card-header"><h2>Descrição técnica</h2></div>
        <?php if ($sections['overview']): ?>
            <ul class="sv-specs">
                <?php foreach ($sections['overview'] as $line): ?>
                    <li><?= e($line) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="empty">Sem descrição cadastrada — edite o registro para adicionar.</p>
        <?php endif; ?>

        <?php if ($sections['bench']): ?>
            <div class="sv-subtitle">Observações de bancada</div>
            <ul class="sv-bench">
                <?php foreach ($sections['bench'] as $line): ?>
                    <li><?= e($line) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-header"><h2>Medições de bancada</h2></div>
        <?php if ($sections['measurements']): ?>
            <ul class="sv-meas">
                <?php foreach ($sections['measurements'] as $line): ?>
                    <li><?= e($line) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="empty">Sem medições cadastradas.</p>
        <?php endif; ?>
    </section>
</div>

<!-- Defeitos comuns e resoluções -->
<?php if ($resolutions): ?>
    <section class="card">
        <div class="card-header">
            <h2>Defeitos comuns &amp; resolução sugerida</h2>
            <span class="bcol-count"><?= count($resolutions) ?></span>
        </div>
        <div class="sv-fixes">
            <?php foreach ($resolutions as $item): ?>
                <div class="sv-fix">
                    <div class="sv-fix-defect">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                        <?= e(mb_strtoupper(mb_substr($item['defect'], 0, 1)) . mb_substr($item['defect'], 1)) ?>
                    </div>
                    <div class="sv-fix-solution"><?= e($item['resolution']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
