<div class="page-actions">
    <a href="/knowledge/files" class="btn btn-ghost">← Arquivos</a>
    <span class="spacer"></span>
    <?php if ($asset['external_link']): ?><a class="btn btn-primary" href="<?= e($asset['external_link']) ?>" target="_blank" rel="noopener noreferrer">Abrir link</a><?php endif; ?>
    <?php if ($asset['storage_path']): ?><a class="btn btn-primary" href="/knowledge/assets/file?id=<?= (int) $asset['id'] ?>">Baixar</a><?php endif; ?>
    <?php if (knowledge_can('asset_manage')): ?>
        <form method="post" action="/knowledge/assets/delete" class="inline" data-confirm="Arquivar este arquivo?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $asset['id'] ?>">
            <button type="submit" class="btn btn-danger-ghost">Arquivar</button>
        </form>
    <?php endif; ?>
</div>
<section class="card">
    <div class="card-header">
        <h2><?= e($asset['title']) ?></h2>
        <span class="badge badge-code"><?= e($asset['category']) ?></span>
    </div>
    <dl class="detail-grid">
        <div><dt>Fabricante/modelo</dt><dd><?= e(trim(($asset['manufacturer_name'] ?? '') . ' ' . ($asset['model_code'] ?: $asset['model_name'] ?: '')) ?: '-') ?></dd></div>
        <div><dt>Arquivo original</dt><dd><?= e($asset['original_name'] ?: '-') ?></dd></div>
        <div><dt>MIME</dt><dd><?= e($asset['mime_type'] ?: '-') ?></dd></div>
        <div><dt>Tamanho</dt><dd><?= $asset['size_bytes'] ? e(number_format(((int) $asset['size_bytes']) / 1024 / 1024, 2, ',', '.') . ' MB') : '-' ?></dd></div>
        <div><dt>Hash</dt><dd><code><?= e($asset['file_hash'] ?: '-') ?></code></dd></div>
        <div><dt>Usuário</dt><dd><?= e($asset['user_name'] ?: '-') ?></dd></div>
        <div><dt>Artigo</dt><dd><?= $asset['article_id'] ? '<a class="link" href="/knowledge/articles/view?id=' . (int) $asset['article_id'] . '">' . e($asset['article_title']) . '</a>' : '-' ?></dd></div>
        <div><dt>Reparo</dt><dd><?= $asset['repair_id'] ? '<a class="link" href="/knowledge/repairs/view?id=' . (int) $asset['repair_id'] . '">' . e($asset['repair_title']) . '</a>' : '-' ?></dd></div>
        <div class="span-all"><dt>Descrição</dt><dd><?= $asset['description'] ? nl2br(e($asset['description'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Tags</dt><dd><?= e($asset['tags'] ?: '-') ?></dd></div>
    </dl>
</section>
