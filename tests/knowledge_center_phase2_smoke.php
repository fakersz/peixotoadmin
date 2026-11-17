<?php
/**
 * Smoke test da Fase 2 do Centro de Conhecimento.
 *
 * Uso:
 *   php tests/knowledge_center_phase2_smoke.php
 */

require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/middleware/auth.php';
require APP_PATH . '/controllers/knowledge.php';

function assert_true(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('Falhou: ' . $label);
    }
    echo "ok\t" . $label . "\n";
}

function assert_throws(callable $fn, string $label): void
{
    try {
        $fn();
    } catch (Throwable $exception) {
        echo "ok\t" . $label . "\n";
        return;
    }
    throw new RuntimeException('Falhou: ' . $label);
}

$pdo = db();
$user = $pdo->query("SELECT id, role FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetch()
    ?: $pdo->query('SELECT id, role FROM users ORDER BY id LIMIT 1')->fetch();
assert_true((bool) $user, 'usuario existente');
$_SESSION['user_id'] = (int) $user['id'];
assert_true(knowledge_can('article_create'), 'permissao de criar artigo');

$csrfScript = 'require "app/config/config.php"; require APP_PATH . "/helpers/functions.php"; $_SESSION["csrf_token"] = "known"; $_POST["_csrf"] = "bad"; verify_csrf();';
$csrfOutput = [];
exec(PHP_BINARY . ' -r ' . escapeshellarg($csrfScript), $csrfOutput);
assert_true(str_contains(implode("\n", $csrfOutput), 'token inválido'), 'CSRF invalido bloqueado');

$permissionScript = <<<'PHP'
require "app/config/config.php";
require APP_PATH . "/config/database.php";
require APP_PATH . "/helpers/functions.php";
require APP_PATH . "/middleware/auth.php";
require APP_PATH . "/models/Knowledge.php";
$pdo = db();
$pdo->beginTransaction();
$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('Usuario Smoke', 'usuario-smoke@example.com', ?, 'usuario') RETURNING id");
$stmt->execute([password_hash('smoke', PASSWORD_DEFAULT)]);
$_SESSION['user_id'] = (int) $stmt->fetchColumn();
echo knowledge_can('article_create') ? 'bad' : 'ok';
$pdo->rollBack();
PHP;
$permissionOutput = [];
exec(PHP_BINARY . ' -r ' . escapeshellarg($permissionScript), $permissionOutput);
assert_true(trim(implode('', $permissionOutput)) === 'ok', 'usuario sem permissao de criacao bloqueado');

$pdo->beginTransaction();
try {
    $manufacturerId = knowledge_manufacturer_create(['name' => 'Teste Áudio Phase2']);
    assert_true($manufacturerId > 0, 'cria fabricante normalizado');
    assert_throws(fn () => knowledge_manufacturer_create(['name' => 'teste audio phase2']), 'bloqueia fabricante duplicado normalizado');

    $modelId = knowledge_model_create([
        'manufacturer_id' => $manufacturerId,
        'name' => 'Modelo Teste',
        'code' => '820-TESTE',
        'description' => 'Modelo para smoke',
        'tags' => 'phase2, placa',
    ]);
    assert_true($modelId > 0, 'cria modelo');
    assert_throws(fn () => knowledge_model_create([
        'manufacturer_id' => $manufacturerId,
        'name' => 'Modelo Teste',
        'code' => '820 TESTE',
    ]), 'bloqueia modelo duplicado por nome/codigo normalizado');

    $articleId = knowledge_article_create([
        'manufacturer_id' => $manufacturerId,
        'model_id' => $modelId,
        'title' => 'Phase2 artigo PPBUS',
        'slug' => 'phase2-artigo-ppbus',
        'type' => 'procedimento',
        'status' => 'rascunho',
        'category' => 'energia',
        'summary' => 'Resumo PPBUS',
        'symptoms' => 'Nao liga',
        'diagnosis' => 'PPBUS ausente',
        'procedure' => 'Medir PPBUS',
        'solution' => 'Substituir componente',
        'tools' => 'Multimetro',
        'parts_used' => 'CI teste',
        'avg_time_minutes' => 35,
        'difficulty' => 'media',
        'notes' => 'Smoke',
        'content' => "## Procedimento\n- [ ] medir linha\n| Linha | Valor |\n| --- | --- |\n| PPBUS | 12.6V |",
        'tags' => 'phase2, ppbus',
        'favorite' => false,
        'author_user_id' => (int) $user['id'],
    ]);
    assert_true($articleId > 0, 'cria artigo completo');

    knowledge_article_update($articleId, [
        'manufacturer_id' => $manufacturerId,
        'model_id' => $modelId,
        'title' => 'Phase2 artigo PPBUS atualizado',
        'slug' => 'phase2-artigo-ppbus',
        'type' => 'procedimento',
        'status' => 'publicado',
        'category' => 'energia',
        'summary' => 'Resumo PPBUS',
        'symptoms' => 'Nao liga',
        'diagnosis' => 'PPBUS ausente',
        'procedure' => 'Medir PPBUS',
        'solution' => 'Substituir componente',
        'tools' => 'Multimetro',
        'parts_used' => 'CI teste',
        'avg_time_minutes' => 35,
        'difficulty' => 'media',
        'notes' => 'Smoke',
        'content' => 'Conteudo PPBUS atualizado',
        'tags' => 'phase2, ppbus',
        'author_user_id' => (int) $user['id'],
    ]);
    $history = knowledge_article_history_all($articleId);
    assert_true(count($history) >= 2, 'registra historico de artigo');

    $favOn = knowledge_favorite_toggle((int) $user['id'], 'article', $articleId);
    assert_true($favOn, 'adiciona favorito por usuario');
    assert_true(count(knowledge_favorites_for_user((int) $user['id'])) >= 1, 'lista favoritos por usuario');

    $repairId = knowledge_repair_create([
        'manufacturer_id' => $manufacturerId,
        'model_id' => $modelId,
        'title' => 'Phase2 reparo PPBUS',
        'problem' => 'Nao liga',
        'symptoms' => 'Sem consumo',
        'diagnosis' => 'Curto PPBUS',
        'tests_performed' => 'Fonte assimetrica',
        'solution' => 'Remover curto',
        'parts' => 'Capacitor',
        'tools' => 'Fonte, multimetro',
        'time_minutes' => 45,
        'cost' => 120,
        'observations' => 'Smoke',
        'result' => 'resolvido',
        'repair_date' => date('Y-m-d'),
        'tags' => 'phase2, curto',
        'status' => 'rascunho',
        'author_user_id' => (int) $user['id'],
    ]);
    assert_true($repairId > 0, 'cria reparo completo');

    $convertedArticleId = knowledge_repair_convert_to_article($repairId, (int) $user['id']);
    assert_true($convertedArticleId > 0, 'converte reparo em artigo rascunho');

    $results = knowledge_search('PPBUS', 20, ['manufacturer_id' => $manufacturerId]);
    assert_true(count($results) >= 1, 'busca com filtro retorna resultados');
    assert_true(count(knowledge_recent_searches()) >= 1, 'registra log de busca');

    $assetId = knowledge_asset_create([
        'manufacturer_id' => $manufacturerId,
        'model_id' => $modelId,
        'category' => 'link',
        'title' => 'Video externo phase2',
        'external_link' => 'https://example.com/video',
        'description' => 'Link externo para video grande',
        'tags' => 'phase2',
        'user_id' => (int) $user['id'],
    ]);
    assert_true($assetId > 0, 'cria asset de link externo');

    $tmp = tempnam(sys_get_temp_dir(), 'kc_');
    file_put_contents($tmp, '<?php echo 1;');
    $_FILES['asset_file'] = [
        'name' => 'shell.php',
        'type' => 'application/x-php',
        'tmp_name' => $tmp,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmp),
    ];
    assert_throws(fn () => knowledge_handle_asset_upload('arquivo'), 'bloqueia executavel em upload');
    @unlink($tmp);
    unset($_FILES['asset_file']);

    $tmp = tempnam(sys_get_temp_dir(), 'kc_');
    file_put_contents($tmp, 'isso nao e pdf');
    $_FILES['asset_file'] = [
        'name' => 'manual.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tmp,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmp),
    ];
    assert_throws(fn () => knowledge_handle_asset_upload('pdf'), 'bloqueia upload com MIME invalido');
    @unlink($tmp);
    unset($_FILES['asset_file']);

    $clientId = (int) $pdo->query(
        "INSERT INTO clients (name, phone, email, notes) VALUES ('Cliente Phase2', '11999999999', 'phase2@example.com', 'smoke') RETURNING id"
    )->fetchColumn();
    $deviceStmt = $pdo->prepare(
        "INSERT INTO devices (client_id, category, brand, model, model_code, serial_number)
         VALUES (?, 'MacBook', 'Apple', 'MacBook Pro', 'A_PHASE2', 'SERIAL_PHASE2') RETURNING id"
    );
    $deviceStmt->execute([$clientId]);
    $deviceId = (int) $deviceStmt->fetchColumn();
    $orderStmt = $pdo->prepare(
        "INSERT INTO service_orders
            (client_id, device_id, title, reported_issue, diagnosis, solution, status, priority,
             labor_price, parts_price, total_price, paid_amount, payment_status)
         VALUES (?, ?, 'OS Phase2', 'Nao liga', 'PPBUS ausente', 'Reparo teste', 'entregue', 'normal',
                 100, 20, 120, 120, 'pago') RETURNING id"
    );
    $orderStmt->execute([$clientId, $deviceId]);
    $fullOrder = order_find((int) $orderStmt->fetchColumn());
    $repairFromOrder = knowledge_repair_from_order($fullOrder, (int) $user['id']);
    $repairFromOrderAgain = knowledge_repair_from_order($fullOrder, (int) $user['id']);
    assert_true($repairFromOrder === $repairFromOrderAgain, 'nao duplica reparo criado a partir da OS');

    assert_true(str_contains(md('- [x] teste'), 'checkbox'), 'markdown renderiza checklist sanitizado');

    $pdo->rollBack();
    echo "ok\trollback phase2 aplicado\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

echo "ok\tcentro de conhecimento phase2 smoke concluido\n";
