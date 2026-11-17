<?php
/**
 * Smoke test do Centro de Conhecimento.
 *
 * Uso:
 *   php tests/knowledge_center_smoke.php
 */

require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/middleware/auth.php';
require APP_PATH . '/models/Knowledge.php';
require APP_PATH . '/models/Schematic.php';

function assert_true(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('Falhou: ' . $label);
    }
    echo "ok\t" . $label . "\n";
}

$pdo = db();
$user = $pdo->query('SELECT id, role FROM users ORDER BY id LIMIT 1')->fetch();
assert_true((bool) $user, 'usuario existente');
$_SESSION['user_id'] = (int) $user['id'];
assert_true(user_can('view'), 'permissao view disponivel');

assert_true((int) $pdo->query('SELECT COUNT(*) FROM knowledge_manufacturers')->fetchColumn() >= 14, 'fabricantes base criados');
assert_true((int) $pdo->query('SELECT COUNT(*) FROM schematics')->fetchColumn() >= 1, 'schematics preservado');
assert_true((int) $pdo->query('SELECT COUNT(*) FROM schematics WHERE knowledge_model_id IS NOT NULL')->fetchColumn() >= 1, 'schematic vinculado a modelo');

$results = knowledge_search('A1502');
assert_true(count($results) >= 1, 'busca full-text retorna A1502');

$stats = knowledge_dashboard_stats();
assert_true($stats['schematics'] >= 1, 'estatistica de esquemas');
assert_true($stats['models'] >= 1, 'estatistica de modelos');

$related = knowledge_related_for_device([
    'brand' => 'Apple',
    'model' => 'MacBook Pro',
    'model_code' => 'A1502',
    'category' => 'MacBook',
]);
assert_true($related['schematics'] >= 1, 'OS encontra esquema relacionado');

$pdo->beginTransaction();
try {
    $articleId = knowledge_article_create([
        'manufacturer_id' => null,
        'model_id' => null,
        'title' => 'Smoke artigo centro',
        'type' => 'artigo',
        'status' => 'rascunho',
        'summary' => 'Resumo smoke',
        'content' => 'Conteudo smoke PPBUS',
        'tags' => 'smoke, ppbus',
        'favorite' => false,
        'author_user_id' => (int) $user['id'],
    ]);
    assert_true($articleId > 0, 'cria artigo em rascunho');

    $repairId = knowledge_repair_create([
        'manufacturer_id' => null,
        'model_id' => null,
        'service_order_id' => null,
        'client_id' => null,
        'device_id' => null,
        'title' => 'Smoke reparo centro',
        'problem' => 'Nao liga',
        'diagnosis' => 'PPBUS ausente',
        'solution' => 'Teste smoke',
        'parts' => 'Nenhuma',
        'time_minutes' => 10,
        'observations' => 'Rollback',
        'tags' => 'smoke',
        'status' => 'rascunho',
        'author_user_id' => (int) $user['id'],
    ]);
    assert_true($repairId > 0, 'cria reparo em rascunho');
    $pdo->rollBack();
    echo "ok\trollback de artigo/reparo aplicado\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

echo "ok\tcentro de conhecimento smoke concluido\n";
