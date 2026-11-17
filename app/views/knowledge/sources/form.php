<section class="card card-form">
    <form method="post" data-validate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field span-2"><label for="title">Título *</label><input type="text" id="title" name="title" required maxlength="220" value="<?= e($source['title'] ?? '') ?>"></div>
            <div class="field"><label for="organization">Organização</label><input type="text" id="organization" name="organization" maxlength="120" placeholder="Apple Support, iFixit..." value="<?= e($source['organization'] ?? '') ?>"></div>
            <div class="field"><label for="source_type">Tipo</label><input type="text" id="source_type" name="source_type" maxlength="40" placeholder="oficial, comunidade, manual..." value="<?= e($source['source_type'] ?? '') ?>"></div>
            <div class="field span-2"><label for="url">URL *</label><input type="url" id="url" name="url" required maxlength="600" value="<?= e($source['url'] ?? '') ?>"></div>
            <div class="field span-2"><label for="canonical_url">URL canônica</label><input type="url" id="canonical_url" name="canonical_url" maxlength="600" value="<?= e($source['canonical_url'] ?? '') ?>"></div>
            <div class="field"><label for="published_at">Publicada em</label><input type="date" id="published_at" name="published_at" value="<?= e($source['published_at'] ?? '') ?>"></div>
            <div class="field"><label for="checked_at">Verificada em</label><input type="date" id="checked_at" name="checked_at" value="<?= e($source['checked_at'] ?? date('Y-m-d')) ?>"></div>
            <div class="field"><label for="language">Idioma</label><input type="text" id="language" name="language" maxlength="10" placeholder="pt, en" value="<?= e($source['language'] ?? '') ?>"></div>
            <div class="field"><label for="license">Licença</label><input type="text" id="license" name="license" maxlength="80" value="<?= e($source['license'] ?? '') ?>"></div>
            <div class="field"><label for="availability_status">Status de disponibilidade</label>
                <select id="availability_status" name="availability_status">
                    <?php foreach (KNOWLEDGE_AVAILABILITY_STATUSES as $status): ?>
                        <option value="<?= e($status) ?>"><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label for="confidence_level">Nível de confiança</label>
                <select id="confidence_level" name="confidence_level">
                    <?php foreach (KNOWLEDGE_CONFIDENCE_LEVELS as $level): ?>
                        <option value="<?= e($level) ?>"><?= e($level) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field span-2"><label for="notes">Observações</label><textarea id="notes" name="notes" rows="3"><?= e($source['notes'] ?? '') ?></textarea></div>
        </div>
        <div class="form-actions">
            <a href="/knowledge/sources" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</section>
