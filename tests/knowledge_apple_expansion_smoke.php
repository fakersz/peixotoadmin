<?php
/**
 * Smoke test da expansão do Centro de Conhecimento (catálogo Apple).
 *
 * Uso:
 *   php tests/knowledge_apple_expansion_smoke.php
 */

require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/middleware/auth.php';
require APP_PATH . '/models/Knowledge.php';

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

$migrationSql = file_get_contents(__DIR__ . '/../database/migrations/009_apple_knowledge_expansion.sql');
$pdo->exec($migrationSql);
$pdo->exec($migrationSql);
assert_true(true, 'migration 009 idempotente (executada duas vezes)');

$user = $pdo->query("SELECT id FROM users ORDER BY id LIMIT 1")->fetch();
assert_true((bool) $user, 'usuario existente');
$_SESSION['user_id'] = (int) $user['id'];
$userId = (int) $user['id'];

$pdo->beginTransaction();
try {
    $manufacturerId = knowledge_manufacturer_create(['name' => 'Teste Apple Expansion']);

    // --- A-number reaproveitado: dois modelos oficiais distintos com o mesmo code ---
    $modelA = knowledge_model_create([
        'manufacturer_id' => $manufacturerId,
        'name' => 'Modelo Teste (Ano 1)',
        'code' => 'A9999',
        'family' => 'Teste',
        'release_year' => 2020,
        'confidence_level' => 'oficial',
    ]);
    $modelB = knowledge_model_create([
        'manufacturer_id' => $manufacturerId,
        'name' => 'Modelo Teste (Ano 2)',
        'code' => 'A9999',
        'family' => 'Teste',
        'release_year' => 2021,
        'confidence_level' => 'oficial',
    ]);
    assert_true($modelA > 0 && $modelB > 0 && $modelA !== $modelB, 'permite dois modelos oficiais com o mesmo A-number (code)');
    assert_throws(
        fn () => knowledge_model_create(['manufacturer_id' => $manufacturerId, 'name' => 'Modelo Teste (Ano 1)', 'code' => 'A0001']),
        'ainda bloqueia duplicidade por nome normalizado igual'
    );

    $foundByDedupe = knowledge_model_find_by_dedupe($manufacturerId, 'Modelo Teste (Ano 1)', 2020);
    assert_true($foundByDedupe && (int) $foundByDedupe['id'] === $modelA, 'dedupe de modelo encontra por fabricante+nome+ano');

    // --- Identificadores: upsert idempotente ---
    $identifierData = ['model_id' => $modelA, 'identifier_type' => 'emc', 'identifier_value' => '9999', 'is_primary' => true];
    $id1 = knowledge_model_identifier_upsert($identifierData);
    $id2 = knowledge_model_identifier_upsert($identifierData);
    assert_true($id1 === $id2, 'upsert de identificador e idempotente (mesma linha)');
    assert_true(count(knowledge_model_identifiers_for_model($modelA)) === 1, 'nao duplica identificador ao repetir upsert');

    // --- Variantes: upsert idempotente ---
    $variantData = ['model_id' => $modelA, 'variant_name' => 'Config Base', 'processor' => 'Chip X', 'is_standard' => true];
    $v1 = knowledge_model_variant_upsert($variantData);
    $v2 = knowledge_model_variant_upsert($variantData);
    assert_true($v1 === $v2, 'upsert de variante e idempotente');
    assert_true(count(knowledge_model_variants_for_model($modelA)) === 1, 'nao duplica variante ao repetir upsert');

    // --- Placas logicas: dedupe por board_code+revision, sem exigir unicidade global do A-number ---
    $boardData = ['board_code' => '820-99999', 'revision' => 'A', 'year' => 2020, 'verification_status' => 'unverified'];
    $b1 = knowledge_logic_board_upsert($boardData);
    $b2 = knowledge_logic_board_upsert(array_merge($boardData, ['description' => 'Atualizado']));
    assert_true($b1 === $b2, 'upsert de placa logica e idempotente (update, nao duplica)');
    $board = knowledge_logic_board_find($b1);
    assert_true($board['description'] === 'Atualizado', 'update de placa aplica novos campos sem quebrar (regressao do bug de parametro extra)');

    knowledge_model_logic_board_link($modelA, $b1, 'nota 1');
    knowledge_model_logic_board_link($modelA, $b1, 'nota 2');
    assert_true(count(knowledge_logic_boards_for_model($modelA)) === 1, 'link modelo<->placa e idempotente (ON CONFLICT)');
    assert_true(count(knowledge_models_for_board($b1)) === 1, 'busca reversa placa->modelos funciona');

    // --- Fontes: dedupe por URL, update sem erro de parametro extra (regressao) ---
    $sourceData = ['title' => 'Fonte Teste', 'url' => 'https://example.com/fonte-teste', 'availability_status' => 'available_official'];
    $s1 = knowledge_source_upsert($sourceData);
    $s2 = knowledge_source_upsert(array_merge($sourceData, ['title' => 'Fonte Teste Atualizada']));
    assert_true($s1 === $s2, 'upsert de fonte e idempotente (update por URL)');
    assert_true(knowledge_source_find($s1)['title'] === 'Fonte Teste Atualizada', 'update de fonte aplica novo titulo (regressao do bug de :url extra)');

    // --- Evidencias: dedupe por (entity_type, entity_id, field_name, source_id) ---
    $evidenceData = ['entity_type' => 'model', 'entity_id' => $modelA, 'field_name' => 'emc', 'field_value' => '9999', 'source_id' => $s1];
    $e1 = knowledge_source_evidence_create($evidenceData);
    $e2 = knowledge_source_evidence_create(array_merge($evidenceData, ['field_value' => '9999 (confirmado)']));
    assert_true($e1 === $e2, 'upsert de evidencia e idempotente (nao duplica por reimportacao)');
    assert_true(count(knowledge_source_evidence_for_entity('model', $modelA)) === 1, 'nao duplica evidencia ao repetir upsert');

    // --- Compatibilidade macOS: dedupe por (model_id, os_name, os_version, compatibility_type) ---
    $osData = ['model_id' => $modelA, 'os_version' => 'macOS 99 Teste', 'compatibility_type' => 'original'];
    $o1 = knowledge_os_compatibility_upsert($osData);
    $o2 = knowledge_os_compatibility_upsert(array_merge($osData, ['limitations' => 'Nenhuma']));
    assert_true($o1 === $o2, 'upsert de compatibilidade macOS e idempotente (regressao do bug de parametro extra)');

    // --- Programas de servico: dedupe por official_url ---
    $spData = ['name' => 'Programa Teste', 'official_url' => 'https://apple.com/programa-teste', 'model_ids' => [$modelA]];
    $sp1 = knowledge_service_program_upsert($spData);
    $sp2 = knowledge_service_program_upsert(array_merge($spData, ['name' => 'Programa Teste Atualizado']));
    assert_true($sp1 === $sp2, 'upsert de programa de servico e idempotente (regressao do bug de :official_url extra)');
    assert_true(count(knowledge_service_programs_for_model($modelA)) === 1, 'vincula programa de servico ao modelo sem duplicar');

    // --- Busca global cobre os novos tipos ---
    $results = knowledge_search('A9999', 20);
    $types = array_column($results, 'type');
    assert_true(in_array('model', $types, true), 'busca global encontra modelo por A-number');
    assert_true(count(knowledge_search('820-99999', 20)) >= 1, 'busca global encontra placa por codigo');
    assert_true(count(knowledge_search('Fonte Teste Atualizada', 20)) >= 1, 'busca global encontra fonte por titulo');

    // --- Fila de revisao ---
    $reviewId = knowledge_review_queue_create('model', ['name' => 'Suspeito'], 'Conflito de teste');
    assert_true(count(knowledge_review_queue_all('pendente')) >= 1, 'item entra na fila de revisao');
    knowledge_review_queue_set_status($reviewId, 'resolvido', $userId);
    assert_true(count(knowledge_review_queue_all('resolvido')) >= 1, 'resolve item da fila de revisao');

    // --- Controle de importacao: runs/items com resume ---
    $runId = knowledge_import_run_create('commit', ['teste' => true]);
    knowledge_import_item_upsert($runId, 'chave-teste', 'model', 'insert', 'dedupe-teste', 'done', null, $modelA);
    $found = knowledge_import_item_find($runId, 'chave-teste');
    assert_true($found && (int) $found['created_entity_id'] === $modelA, 'registra item de importacao com entidade criada');
    knowledge_import_run_finish($runId, 'completed', ['ok' => true]);

    $pdo->rollBack();
    echo "ok\trollback aplicado\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

echo "ok\tcentro de conhecimento — expansao apple smoke concluido\n";
