<?php
$article = $article ?? null;
$selectedManufacturer = $article['manufacturer_id'] ?? null;
$selectedModel = $article['model_id'] ?? null;
$isEdit = !empty($article['id']);
?>
<section class="card card-form knowledge-wide-form">
    <form method="post" data-validate>
        <?= csrf_field() ?>
        <?php if (!empty($article['source_order_id'])): ?><input type="hidden" name="source_order_id" value="<?= (int) $article['source_order_id'] ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="field span-2">
                <label for="title">Título *</label>
                <input type="text" id="title" name="title" required maxlength="180" value="<?= e($article['title'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" pattern="[a-z0-9-]{2,220}" maxlength="220" value="<?= e($article['slug'] ?? '') ?>" placeholder="gerado automaticamente">
            </div>
            <div class="field">
                <label for="category">Categoria</label>
                <input type="text" id="category" name="category" maxlength="80" value="<?= e($article['category'] ?? '') ?>" placeholder="placa, tela, energia">
            </div>
            <div class="field">
                <label for="manufacturer_id">Fabricante</label>
                <select id="manufacturer_id" name="manufacturer_id">
                    <option value="">Selecione...</option>
                    <?php foreach ($manufacturers as $manufacturer): ?>
                        <option value="<?= (int) $manufacturer['id'] ?>" <?= (int) $selectedManufacturer === (int) $manufacturer['id'] ? 'selected' : '' ?>><?= e($manufacturer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="model_id">Modelo</label>
                <select id="model_id" name="model_id">
                    <option value="">Selecione...</option>
                    <?php foreach ($models as $model): ?>
                        <option value="<?= (int) $model['id'] ?>" data-manufacturer="<?= (int) $model['manufacturer_id'] ?>" <?= (int) $selectedModel === (int) $model['id'] ? 'selected' : '' ?>>
                            <?= e($model['manufacturer_name'] . ' - ' . ($model['code'] ?: $model['name'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="type">Tipo</label>
                <select id="type" name="type">
                    <?php foreach (KNOWLEDGE_ARTICLE_TYPES as $type): ?>
                        <option value="<?= e($type) ?>" <?= ($article['type'] ?? 'artigo') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (KNOWLEDGE_ARTICLE_STATUSES as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($article['status'] ?? 'rascunho') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="difficulty">Dificuldade</label>
                <select id="difficulty" name="difficulty">
                    <?php foreach (KNOWLEDGE_DIFFICULTIES as $difficulty): ?>
                        <option value="<?= e($difficulty) ?>" <?= ($article['difficulty'] ?? 'media') === $difficulty ? 'selected' : '' ?>><?= e($difficulty) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="avg_time_minutes">Tempo médio (min)</label>
                <input type="number" min="0" id="avg_time_minutes" name="avg_time_minutes" value="<?= e($article['avg_time_minutes'] ?? '') ?>">
            </div>
            <div class="field span-2"><label for="summary">Resumo</label><textarea id="summary" name="summary" rows="2" maxlength="1200"><?= e($article['summary'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="symptoms">Sintomas</label><textarea id="symptoms" name="symptoms" rows="2"><?= e($article['symptoms'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="diagnosis">Diagnóstico</label><textarea id="diagnosis" name="diagnosis" rows="2"><?= e($article['diagnosis'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="procedure">Procedimento</label><textarea id="procedure" name="procedure" rows="3"><?= e($article['procedure'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="solution">Solução</label><textarea id="solution" name="solution" rows="2"><?= e($article['solution'] ?? '') ?></textarea></div>
            <div class="field"><label for="tools">Ferramentas necessárias</label><textarea id="tools" name="tools" rows="3"><?= e($article['tools'] ?? '') ?></textarea></div>
            <div class="field"><label for="parts_used">Peças utilizadas</label><textarea id="parts_used" name="parts_used" rows="3"><?= e($article['parts_used'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="notes">Observações</label><textarea id="notes" name="notes" rows="2"><?= e($article['notes'] ?? '') ?></textarea></div>
            <div class="field span-2">
                <label for="content">Conteúdo principal em Markdown</label>
                <div class="md-editor" data-md-editor>
                    <div class="md-toolbar" role="toolbar">
                        <button type="button" class="btn btn-sm btn-ghost" data-md="bold">B</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="italic">I</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="h2">H2</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="list">Lista</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="check">Check</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="table">Tabela</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="code">Código</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="quote">Citação</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="link">Link</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="image">Imagem</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md="warning">Aviso</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-md-preview>Preview</button>
                    </div>
                    <div class="md-panes">
                        <textarea id="content" name="content" rows="18" class="knowledge-editor" data-md-source><?= e($article['content'] ?? '') ?></textarea>
                        <div class="markdown-body md-preview" data-md-target></div>
                    </div>
                </div>
            </div>
            <div class="field span-2">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" maxlength="255" value="<?= e($article['tags'] ?? '') ?>" placeholder="A2337, PPBUS, bateria">
            </div>
            <label class="check-inline span-2"><input type="checkbox" name="favorite" <?= !empty($article['favorite']) ? 'checked' : '' ?>> Favorito global</label>
        </div>
        <div class="form-actions">
            <a href="<?= $isEdit ? '/knowledge/articles/view?id=' . (int) $article['id'] : '/knowledge/articles' ?>" class="btn btn-ghost">Cancelar</a>
            <button type="submit" name="form_action" value="save_draft" class="btn btn-ghost">Salvar rascunho</button>
            <?php if (knowledge_can('article_publish', $article)): ?>
                <button type="submit" name="form_action" value="publish" class="btn btn-primary">Publicar</button>
            <?php endif; ?>
            <button type="submit" name="form_action" value="save" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</section>
