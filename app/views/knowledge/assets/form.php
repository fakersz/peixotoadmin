<section class="card card-form knowledge-wide-form">
    <form method="post" enctype="multipart/form-data" data-validate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label for="category">Tipo *</label>
                <select id="category" name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= ($asset['category'] ?? 'arquivo') === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="title">Título</label>
                <input type="text" id="title" name="title" maxlength="180" value="<?= e($asset['title'] ?? '') ?>" placeholder="usa o nome do arquivo se vazio">
            </div>
            <div class="field">
                <label for="asset_file">Arquivo</label>
                <input type="file" id="asset_file" name="asset_file">
            </div>
            <div class="field">
                <label for="external_link">Link externo</label>
                <input type="url" id="external_link" name="external_link" value="<?= e($asset['external_link'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="field">
                <label for="manufacturer_id">Fabricante</label>
                <select id="manufacturer_id" name="manufacturer_id">
                    <option value="">Selecione...</option>
                    <?php foreach ($manufacturers as $manufacturer): ?>
                        <option value="<?= (int) $manufacturer['id'] ?>" <?= (int) ($asset['manufacturer_id'] ?? 0) === (int) $manufacturer['id'] ? 'selected' : '' ?>><?= e($manufacturer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="model_id">Modelo</label>
                <select id="model_id" name="model_id">
                    <option value="">Sem modelo</option>
                    <?php foreach ($models as $model): ?>
                        <option value="<?= (int) $model['id'] ?>" data-manufacturer="<?= (int) $model['manufacturer_id'] ?>" <?= (int) ($asset['model_id'] ?? ($_GET['model_id'] ?? 0)) === (int) $model['id'] ? 'selected' : '' ?>><?= e($model['manufacturer_name'] . ' - ' . ($model['code'] ?: $model['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="article_id">Artigo relacionado</label>
                <select id="article_id" name="article_id">
                    <option value="">Sem artigo</option>
                    <?php foreach ($articles as $article): ?>
                        <option value="<?= (int) $article['id'] ?>" <?= (int) ($asset['article_id'] ?? ($_GET['article_id'] ?? 0)) === (int) $article['id'] ? 'selected' : '' ?>><?= e($article['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="repair_id">Reparo relacionado</label>
                <select id="repair_id" name="repair_id">
                    <option value="">Sem reparo</option>
                    <?php foreach ($repairs as $repair): ?>
                        <option value="<?= (int) $repair['id'] ?>" <?= (int) ($asset['repair_id'] ?? ($_GET['repair_id'] ?? 0)) === (int) $repair['id'] ? 'selected' : '' ?>><?= e($repair['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field span-2"><label for="description">Descrição</label><textarea id="description" name="description" rows="3"><?= e($asset['description'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="tags">Tags</label><input type="text" id="tags" name="tags" maxlength="255" value="<?= e($asset['tags'] ?? '') ?>"></div>
        </div>
        <div class="form-actions">
            <a href="/knowledge/files" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar arquivo</button>
        </div>
    </form>
</section>
