<section class="card card-form">
    <form method="post" data-validate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field"><label for="board_code">Código base *</label><input type="text" id="board_code" name="board_code" required maxlength="30" placeholder="820-3536" value="<?= e($board['board_code'] ?? '') ?>"></div>
            <div class="field"><label for="revision">Revisão</label><input type="text" id="revision" name="revision" maxlength="10" placeholder="A" value="<?= e($board['revision'] ?? '') ?>"></div>
            <div class="field"><label for="full_code">Código completo</label><input type="text" id="full_code" name="full_code" maxlength="50" placeholder="820-3536-A" value="<?= e($board['full_code'] ?? '') ?>"></div>
            <div class="field"><label for="board_id">Board ID</label><input type="text" id="board_id" name="board_id" maxlength="60" value="<?= e($board['board_id'] ?? '') ?>"></div>
            <div class="field"><label for="year">Ano</label><input type="number" id="year" name="year" min="1990" max="2100" value="<?= e((string) ($board['year'] ?? '')) ?>"></div>
            <div class="field"><label for="architecture">Arquitetura</label><input type="text" id="architecture" name="architecture" maxlength="30" placeholder="Intel / Apple Silicon" value="<?= e($board['architecture'] ?? '') ?>"></div>
            <div class="field"><label for="processor">Processador</label><input type="text" id="processor" name="processor" maxlength="120" value="<?= e($board['processor'] ?? '') ?>"></div>
            <div class="field"><label for="gpu">GPU</label><input type="text" id="gpu" name="gpu" maxlength="120" value="<?= e($board['gpu'] ?? '') ?>"></div>
            <div class="field"><label for="memory">Memória</label><input type="text" id="memory" name="memory" maxlength="60" value="<?= e($board['memory'] ?? '') ?>"></div>
            <div class="field"><label for="model_id">Vincular ao modelo</label>
                <select id="model_id" name="model_id">
                    <option value="">— nenhum —</option>
                    <?php foreach ($models as $model): ?>
                        <option value="<?= (int) $model['id'] ?>"><?= e($model['manufacturer_name'] . ' ' . ($model['code'] ?: $model['name'])) ?></option>
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
            <div class="field"><label for="verification_status">Status de disponibilidade</label>
                <select id="verification_status" name="verification_status">
                    <?php foreach (KNOWLEDGE_AVAILABILITY_STATUSES as $status): ?>
                        <option value="<?= e($status) ?>"><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field span-2"><label for="description">Descrição</label><textarea id="description" name="description" rows="3"><?= e($board['description'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="notes">Observações</label><textarea id="notes" name="notes" rows="3"><?= e($board['notes'] ?? '') ?></textarea></div>
        </div>
        <div class="form-actions">
            <a href="/knowledge/boards" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</section>
