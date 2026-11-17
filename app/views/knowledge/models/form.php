<section class="card card-form knowledge-wide-form">
    <form method="post" data-validate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label for="manufacturer_id">Fabricante *</label>
                <select id="manufacturer_id" name="manufacturer_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($manufacturers as $manufacturer): ?>
                        <option value="<?= (int) $manufacturer['id'] ?>" <?= (int) ($model['manufacturer_id'] ?? 0) === (int) $manufacturer['id'] ? 'selected' : '' ?>><?= e($manufacturer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="parent_id">Modelo pai</label>
                <select id="parent_id" name="parent_id">
                    <option value="">Sem modelo pai</option>
                    <?php foreach ($models as $candidate): ?>
                        <?php if ((int) ($model['id'] ?? 0) === (int) $candidate['id']) continue; ?>
                        <option value="<?= (int) $candidate['id'] ?>" <?= (int) ($model['parent_id'] ?? 0) === (int) $candidate['id'] ? 'selected' : '' ?>><?= e($candidate['manufacturer_name'] . ' - ' . ($candidate['code'] ?: $candidate['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="name">Nome *</label>
                <input type="text" id="name" name="name" required maxlength="140" value="<?= e($model['name'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="code">Código (A-number)</label>
                <input type="text" id="code" name="code" maxlength="60" value="<?= e($model['code'] ?? '') ?>" placeholder="A2337">
            </div>
            <div class="field"><label for="family">Família</label><input type="text" id="family" name="family" maxlength="60" placeholder="MacBook Pro, iMac, Mac mini..." value="<?= e($model['family'] ?? '') ?>"></div>
            <div class="field"><label for="release_year">Ano de lançamento</label><input type="number" id="release_year" name="release_year" min="1976" max="2100" value="<?= e((string) ($model['release_year'] ?? '')) ?>"></div>
            <div class="field"><label for="release_period">Período</label><input type="text" id="release_period" name="release_period" maxlength="40" placeholder="Early 2015, Late 2020..." value="<?= e($model['release_period'] ?? '') ?>"></div>
            <div class="field"><label for="architecture">Arquitetura</label><input type="text" id="architecture" name="architecture" maxlength="30" placeholder="Intel / Apple Silicon" value="<?= e($model['architecture'] ?? '') ?>"></div>
            <div class="field"><label for="confidence_level">Nível de confiança</label>
                <select id="confidence_level" name="confidence_level">
                    <?php foreach (KNOWLEDGE_CONFIDENCE_LEVELS as $level): ?>
                        <option value="<?= e($level) ?>" <?= ($model['confidence_level'] ?? 'nao_verificado') === $level ? 'selected' : '' ?>><?= e($level) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field span-2"><label for="description">Descrição</label><textarea id="description" name="description" rows="2"><?= e($model['description'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="frequent_issues">Problemas frequentes</label><textarea id="frequent_issues" name="frequent_issues" rows="3"><?= e($model['frequent_issues'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="symptoms">Sintomas comuns</label><textarea id="symptoms" name="symptoms" rows="2"><?= e($model['symptoms'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="diagnosis">Diagnóstico</label><textarea id="diagnosis" name="diagnosis" rows="3"><?= e($model['diagnosis'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="procedures">Procedimentos</label><textarea id="procedures" name="procedures" rows="3"><?= e($model['procedures'] ?? '') ?></textarea></div>
            <div class="field"><label for="comments">Comentários</label><textarea id="comments" name="comments" rows="2"><?= e($model['comments'] ?? '') ?></textarea></div>
            <div class="field"><label for="observations">Observações</label><textarea id="observations" name="observations" rows="2"><?= e($model['observations'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="tags">Tags relacionadas</label><input type="text" id="tags" name="tags" maxlength="255" value="<?= e($model['tags'] ?? '') ?>"></div>
        </div>
        <div class="form-actions">
            <a href="/knowledge/models" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</section>
