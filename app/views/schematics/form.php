<?php $schematicsBase = $schematicsBase ?? '/schematics'; ?>
<section class="card card-form">
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field span-2">
                <label for="title">Título *</label>
                <input type="text" id="title" name="title" required maxlength="160"
                       value="<?= e($schematic['title'] ?? '') ?>"
                       placeholder="Ex: Schematic MacBook Pro A1502 — 820-3536">
            </div>
            <div class="field">
                <label for="device_category">Categoria</label>
                <select id="device_category" name="device_category">
                    <option value="">Selecione…</option>
                    <?php
                    $schematicCats = schematic_categories();
                    $currentCat = $schematic['device_category'] ?? '';
                    if ($currentCat && !in_array($currentCat, $schematicCats, true)) {
                        array_unshift($schematicCats, $currentCat); // preserva categorias antigas
                    }
                    ?>
                    <?php foreach ($schematicCats as $category): ?>
                        <option value="<?= e($category) ?>" <?= ($schematic['device_category'] ?? '') === $category ? 'selected' : '' ?>>
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="model_code">Modelo Apple</label>
                <input type="text" id="model_code" name="model_code" maxlength="20" class="uppercase"
                       value="<?= e($schematic['model_code'] ?? '') ?>" placeholder="A1502">
            </div>
            <div class="field">
                <label for="board_code">Código da placa</label>
                <input type="text" id="board_code" name="board_code" maxlength="30" class="uppercase"
                       value="<?= e($schematic['board_code'] ?? '') ?>" placeholder="820-3536">
            </div>
            <div class="field">
                <label for="year">Ano</label>
                <input type="number" id="year" name="year" min="1998" max="2100"
                       value="<?= e($schematic['year'] ?? '') ?>" placeholder="2015">
            </div>
            <div class="field span-2">
                <label for="pdf">Arquivo do esquema <?= !empty($schematic['file_path']) ? '(enviar um novo substitui o atual)' : '' ?></label>
                <input type="file" id="pdf" name="pdf"
                       accept=".pdf,.brd,.bdv,.bv,.cad,.fz,.tvw,.asc,.txt,.png,.jpg,.jpeg,.gif,.webp,.zip,.rar,.7z">
                <small class="hint">Aceita PDF, boardview (.brd, .bdv, .bv, .cad, .fz, .tvw), imagens, .txt/.asc e pacotes (.zip, .rar, .7z) — até 40 MB.</small>
                <?php if (!empty($schematic['file_path'])): ?>
                    <?php $ext = strtoupper(pathinfo($schematic['file_path'], PATHINFO_EXTENSION)); ?>
                    <small class="hint">
                        Arquivo atual (<?= e($ext) ?>):
                        <a href="<?= e($schematicsBase) ?>/file?id=<?= $schematic['id'] ?>" target="_blank" class="link">abrir/baixar</a>
                        ·
                        <input type="hidden" name="id" value="<?= $schematic['id'] ?>">
                        <button type="submit" class="link-danger-btn"
                                formaction="<?= e($schematicsBase) ?>/delete-file" formmethod="post" formnovalidate
                                onclick="return confirm('Remover o arquivo anexado? O registro do catálogo será mantido.')">
                            remover arquivo
                        </button>
                    </small>
                <?php endif; ?>
            </div>
            <div class="field span-2">
                <label for="external_link">Link externo (opcional)</label>
                <input type="url" id="external_link" name="external_link" maxlength="500"
                       value="<?= e($schematic['external_link'] ?? '') ?>" placeholder="https://…">
            </div>
            <div class="field span-2">
                <label for="notes">Observações técnicas</label>
                <textarea id="notes" name="notes" rows="3"
                          placeholder="Pontos de medição, defeitos comuns, tensões…"><?= e($schematic['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="<?= e($schematicsBase) ?>" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $schematic ? 'Salvar alterações' : 'Cadastrar esquema' ?></button>
        </div>
    </form>
</section>
