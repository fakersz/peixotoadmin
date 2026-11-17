<section class="card card-form">
    <form method="post" data-validate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field span-2">
                <label for="name">Nome do fabricante *</label>
                <input type="text" id="name" name="name" required maxlength="80" value="<?= e($manufacturer['name'] ?? '') ?>">
            </div>
        </div>
        <div class="form-actions">
            <a href="/knowledge/manufacturers" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</section>
