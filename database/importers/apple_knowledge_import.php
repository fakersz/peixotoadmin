#!/usr/bin/env php
<?php
/**
 * Importador do catálogo de conhecimento Apple — staging (JSON/JSONL) -> banco.
 * Uso: php database/importers/apple_knowledge_import.php [opções]
 *
 * Opções:
 *   --dry-run            Não escreve no banco (padrão quando nenhum modo é informado).
 *   --commit              Escreve de fato no banco.
 *   --validate-only        Só valida a estrutura do staging, sem calcular dedupe nem escrever.
 *   --family=NOME          Processa só modelos dessa família (ex.: "MacBook Pro").
 *   --year-from=AAAA        Ano mínimo de lançamento.
 *   --year-to=AAAA          Ano máximo de lançamento.
 *   --limit=N              Limite de modelos processados nesta execução.
 *   --resume=RUN_ID         Retoma um run anterior, pulando itens já concluídos.
 */

require __DIR__ . '/../../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/models/Knowledge.php';

const STAGING_DIR = __DIR__ . '/../../storage/imports/apple-knowledge';

function import_opt(array $opts, string $name, $default = null)
{
    return array_key_exists($name, $opts) ? $opts[$name] : $default;
}

function import_read_jsonl(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $items = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $lineNo => $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $data = json_decode($line, true);
        if (!is_array($data)) {
            fwrite(STDERR, sprintf("AVISO: linha inválida em %s:%d — ignorada.\n", basename($path), $lineNo + 1));
            continue;
        }
        $items[] = $data;
    }
    return $items;
}

function import_read_json(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

/** Reconstrói o mapa staging_key -> id (ou true, quando não gera entidade própria) para um tipo, a partir de um run já iniciado (suporta --resume). */
function import_load_key_map(int $runId, string $entityType): array
{
    $stmt = db()->prepare(
        "SELECT staging_key, created_entity_id FROM knowledge_import_items
         WHERE run_id = ? AND entity_type = ? AND status = 'done' AND action IN ('insert','update')"
    );
    $stmt->execute([$runId, $entityType]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        if ($row['created_entity_id'] !== null) {
            $map[$row['staging_key']] = (int) $row['created_entity_id'];
        }
    }
    return $map;
}

function import_record(int $runId, string $stagingKey, string $entityType, string $action, ?string $dedupKey, string $status, ?string $error = null, ?int $createdEntityId = null): void
{
    knowledge_import_item_upsert($runId, $stagingKey, $entityType, $action, $dedupKey, $status, $error, $createdEntityId);
}

// ---------------------------------------------------------------
// Handlers dos itens dependentes de modelo (identificadores, variantes,
// placa<->modelo, compatibilidade macOS, artigos).
// ---------------------------------------------------------------

function import_handle_identifier(array $row, array $ctx): array
{
    $modelId = $ctx['modelsMap'][$row['model_key']] ?? null;
    if (!$modelId) {
        return ['action' => 'insert', 'status' => 'error', 'error' => 'model_key não resolvido: ' . $row['model_key']];
    }
    $row['model_id'] = $modelId;
    $row['source_id'] = $ctx['sourcesMap'][$row['source_key'] ?? ''] ?? null;
    $id = knowledge_model_identifier_upsert($row);
    return ['action' => 'insert', 'status' => 'done', 'id' => $id];
}

function import_handle_variant(array $row, array $ctx): array
{
    $modelId = $ctx['modelsMap'][$row['model_key']] ?? null;
    if (!$modelId) {
        return ['action' => 'insert', 'status' => 'error', 'error' => 'model_key não resolvido: ' . $row['model_key']];
    }
    $row['model_id'] = $modelId;
    $row['source_id'] = $ctx['sourcesMap'][$row['source_key'] ?? ''] ?? null;
    $id = knowledge_model_variant_upsert($row);
    return ['action' => 'insert', 'status' => 'done', 'id' => $id];
}

function import_handle_model_board(array $row, array $ctx): array
{
    $modelId = $ctx['modelsMap'][$row['model_key']] ?? null;
    $boardId = $ctx['boardsMap'][$row['board_key']] ?? null;
    if (!$modelId || !$boardId) {
        return ['action' => 'insert', 'status' => 'error', 'error' => 'model_key/board_key não resolvido.'];
    }
    knowledge_model_logic_board_link($modelId, $boardId, $row['compatibility_notes'] ?? null, $ctx['sourcesMap'][$row['source_key'] ?? ''] ?? null);
    return ['action' => 'insert', 'status' => 'done', 'id' => $modelId];
}

function import_handle_os_compat(array $row, array $ctx): array
{
    $modelId = $ctx['modelsMap'][$row['model_key']] ?? null;
    if (!$modelId) {
        return ['action' => 'insert', 'status' => 'error', 'error' => 'model_key não resolvido: ' . $row['model_key']];
    }
    $row['model_id'] = $modelId;
    $row['source_id'] = $ctx['sourcesMap'][$row['source_key'] ?? ''] ?? null;
    $id = knowledge_os_compatibility_upsert($row);
    return ['action' => 'insert', 'status' => 'done', 'id' => $id];
}

function import_handle_evidence(array $row, array $ctx): array
{
    $entityType = (string) ($row['entity_type'] ?? 'model');
    $map = match ($entityType) {
        'logic_board' => $ctx['boardsMap'],
        default => $ctx['modelsMap'],
    };
    $entityId = $map[$row['entity_key']] ?? null;
    $sourceId = $ctx['sourcesMap'][$row['source_key'] ?? ''] ?? null;
    if (!$entityId || !$sourceId) {
        return ['action' => 'insert', 'status' => 'error', 'error' => 'entity_key ou source_key não resolvido: ' . ($row['entity_key'] ?? '?') . ' / ' . ($row['source_key'] ?? '?')];
    }
    $id = knowledge_source_evidence_create([
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'field_name' => $row['field_name'],
        'field_value' => $row['field_value'] ?? null,
        'excerpt' => $row['excerpt'] ?? null,
        'source_id' => $sourceId,
        'checked_at' => $row['checked_at'] ?? null,
        'confidence_level' => $row['confidence_level'] ?? null,
        'has_conflict' => $row['has_conflict'] ?? false,
    ]);
    return ['action' => 'insert', 'status' => 'done', 'id' => $id];
}

function import_handle_article(array $row, array $ctx): array
{
    $modelId = $ctx['modelsMap'][$row['model_key']] ?? null;
    if (!$modelId) {
        return ['action' => 'insert', 'status' => 'error', 'error' => 'model_key não resolvido: ' . $row['model_key']];
    }
    $existing = knowledge_article_from_source('import', $modelId);
    $data = knowledge_article_defaults([
        'manufacturer_id' => $ctx['manufacturerId'],
        'model_id' => $modelId,
        'title' => $row['title'],
        'type' => $row['type'] ?? 'artigo',
        'status' => $row['status'] ?? 'publicado',
        'category' => $row['category'] ?? null,
        'summary' => $row['summary'] ?? null,
        'content' => $row['content'] ?? null,
        'tags' => $row['tags'] ?? null,
        'author_user_id' => $ctx['userId'],
        'source_type' => 'import',
        'source_id' => $modelId,
    ]);
    if ($existing) {
        knowledge_article_update((int) $existing['id'], $data);
        return ['action' => 'update', 'status' => 'done', 'id' => (int) $existing['id']];
    }
    $id = knowledge_article_create($data);
    return ['action' => 'insert', 'status' => 'done', 'id' => $id];
}

// ---------------------------------------------------------------
// Parse de argumentos
// ---------------------------------------------------------------

$opts = getopt('', ['dry-run', 'commit', 'validate-only', 'family:', 'year-from:', 'year-to:', 'limit:', 'resume:']);
$commit = array_key_exists('commit', $opts);
$validateOnly = array_key_exists('validate-only', $opts);
$mode = $validateOnly ? 'validate_only' : ($commit ? 'commit' : 'dry_run');
$familyFilter = import_opt($opts, 'family');
$yearFrom = import_opt($opts, 'year-from') !== null ? (int) $opts['year-from'] : null;
$yearTo = import_opt($opts, 'year-to') !== null ? (int) $opts['year-to'] : null;
$limit = import_opt($opts, 'limit') !== null ? (int) $opts['limit'] : null;
$resumeRunId = import_opt($opts, 'resume') !== null ? (int) $opts['resume'] : null;

$params = ['family' => $familyFilter, 'year_from' => $yearFrom, 'year_to' => $yearTo, 'limit' => $limit, 'mode' => $mode];
$runId = $resumeRunId ?: knowledge_import_run_create($mode, $params);
printf("Run #%d — modo: %s%s\n", $runId, $mode, $resumeRunId ? ' (retomando)' : '');

$summary = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'review' => 0, 'errors' => 0];

try {
    // ---------------------------------------------------------------
    // 1. Fabricantes (assume-se que já existem — só normaliza referências)
    // ---------------------------------------------------------------
    $manufacturersMap = [];
    foreach (import_read_json(STAGING_DIR . '/manufacturers.json') as $row) {
        $existing = knowledge_manufacturer_by_name((string) $row['name']);
        if ($existing) {
            $manufacturersMap[$row['key'] ?? $row['name']] = (int) $existing['id'];
        }
    }
    // Fallback: Apple sempre resolvida pelo nome, mesmo sem staging.
    if (!isset($manufacturersMap['apple'])) {
        $apple = knowledge_manufacturer_by_name('Apple');
        if ($apple) {
            $manufacturersMap['apple'] = (int) $apple['id'];
        }
    }

    // ---------------------------------------------------------------
    // 2. Fontes
    // ---------------------------------------------------------------
    $sourcesMap = array_merge(import_load_key_map($runId, 'source'), []);
    foreach (import_read_jsonl(STAGING_DIR . '/sources.jsonl') as $row) {
        $key = (string) $row['key'];
        if (isset($sourcesMap[$key])) {
            continue;
        }
        if ($validateOnly) {
            import_record($runId, $key, 'source', 'skip', $row['url'] ?? null, 'done');
            continue;
        }
        if (!$commit) {
            import_record($runId, $key, 'source', 'insert', $row['url'] ?? null, 'done');
            $summary['inserted']++;
            continue;
        }
        try {
            $id = knowledge_source_upsert($row);
            $sourcesMap[$key] = $id;
            import_record($runId, $key, 'source', 'insert', $row['url'] ?? null, 'done', null, $id);
            $summary['inserted']++;
        } catch (Throwable $e) {
            import_record($runId, $key, 'source', 'insert', $row['url'] ?? null, 'error', $e->getMessage());
            $summary['errors']++;
        }
    }

    // ---------------------------------------------------------------
    // 3. Placas lógicas
    // ---------------------------------------------------------------
    $boardsMap = import_load_key_map($runId, 'logic_board');
    foreach (import_read_jsonl(STAGING_DIR . '/logic_boards.jsonl') as $row) {
        $key = (string) $row['key'];
        if (isset($boardsMap[$key])) {
            continue;
        }
        $dedupKey = strtoupper((string) $row['board_code']) . '|' . strtoupper((string) ($row['revision'] ?? ''));
        if ($validateOnly) {
            import_record($runId, $key, 'logic_board', 'skip', $dedupKey, 'done');
            continue;
        }
        if (!$commit) {
            import_record($runId, $key, 'logic_board', 'insert', $dedupKey, 'done');
            $summary['inserted']++;
            continue;
        }
        try {
            $row['source_id'] = $sourcesMap[$row['source_key'] ?? ''] ?? null;
            $id = knowledge_logic_board_upsert($row);
            $boardsMap[$key] = $id;
            import_record($runId, $key, 'logic_board', 'insert', $dedupKey, 'done', null, $id);
            $summary['inserted']++;
        } catch (Throwable $e) {
            import_record($runId, $key, 'logic_board', 'insert', $dedupKey, 'error', $e->getMessage());
            $summary['errors']++;
        }
    }

    // ---------------------------------------------------------------
    // 4. Modelos (com filtros de família/ano/limite)
    // ---------------------------------------------------------------
    $modelsMap = import_load_key_map($runId, 'model');
    $processedModels = 0;
    foreach (import_read_jsonl(STAGING_DIR . '/models.jsonl') as $row) {
        $key = (string) $row['key'];
        if (isset($modelsMap[$key])) {
            $processedModels++;
            continue;
        }
        if ($familyFilter && ($row['family'] ?? '') !== $familyFilter) {
            continue;
        }
        $year = $row['release_year'] ?? null;
        if ($yearFrom !== null && ($year === null || $year < $yearFrom)) {
            continue;
        }
        if ($yearTo !== null && ($year === null || $year > $yearTo)) {
            continue;
        }
        if ($limit !== null && $processedModels >= $limit) {
            break;
        }
        $manufacturerId = $manufacturersMap[$row['manufacturer'] ?? 'apple'] ?? $manufacturersMap['apple'] ?? null;
        if (!$manufacturerId) {
            import_record($runId, $key, 'model', 'insert', null, 'error', 'Fabricante não resolvido.');
            $summary['errors']++;
            continue;
        }
        $dedupKey = $manufacturerId . '|' . knowledge_normalize_key((string) $row['name']) . '|' . ($year ?? '');
        if (($row['confidence_level'] ?? null) === 'conflito') {
            import_record($runId, $key, 'model', 'insert', $dedupKey, 'review');
            knowledge_review_queue_create('model', $row, 'Conflito de fonte declarado no staging.');
            $summary['review']++;
            $processedModels++;
            continue;
        }
        if ($validateOnly) {
            import_record($runId, $key, 'model', 'insert', $dedupKey, 'done');
            $processedModels++;
            continue;
        }
        if (!$commit) {
            $existing = knowledge_model_find_by_dedupe($manufacturerId, (string) $row['name'], $year);
            import_record($runId, $key, 'model', $existing ? 'update' : 'insert', $dedupKey, 'done');
            $summary[$existing ? 'updated' : 'inserted']++;
            $processedModels++;
            continue;
        }
        try {
            $existing = knowledge_model_find_by_dedupe($manufacturerId, (string) $row['name'], $year);
            $data = [
                'manufacturer_id' => $manufacturerId,
                'name' => $row['name'],
                'code' => $row['code'] ?? null,
                'description' => $row['description'] ?? null,
                'frequent_issues' => $row['frequent_issues'] ?? null,
                'symptoms' => $row['symptoms'] ?? null,
                'tags' => $row['tags'] ?? null,
                'family' => $row['family'] ?? null,
                'release_year' => $year,
                'release_period' => $row['release_period'] ?? null,
                'architecture' => $row['architecture'] ?? null,
                'confidence_level' => $row['confidence_level'] ?? 'nao_verificado',
            ];
            if ($existing) {
                knowledge_model_update((int) $existing['id'], $data + ['parent_id' => $existing['parent_id']]);
                $id = (int) $existing['id'];
                $action = 'update';
            } else {
                $id = knowledge_model_create($data);
                $action = 'insert';
            }
            $modelsMap[$key] = $id;
            import_record($runId, $key, 'model', $action, $dedupKey, 'done', null, $id);
            $summary[$action === 'insert' ? 'inserted' : 'updated']++;
            $processedModels++;
        } catch (Throwable $e) {
            import_record($runId, $key, 'model', 'insert', $dedupKey, 'error', $e->getMessage());
            $summary['errors']++;
        }
    }

    // ---------------------------------------------------------------
    // 5. Identificadores, variantes, placa<->modelo, compatibilidade macOS,
    //    artigos, programas de serviço, evidências — só para modelos resolvidos acima.
    // ---------------------------------------------------------------
    $dependentSpecs = [
        ['file' => 'model_identifiers.jsonl', 'entity' => 'identifier', 'handler' => 'import_handle_identifier'],
        ['file' => 'model_variants.jsonl', 'entity' => 'variant', 'handler' => 'import_handle_variant'],
        ['file' => 'model_logic_boards.jsonl', 'entity' => 'model_logic_board', 'handler' => 'import_handle_model_board'],
        ['file' => 'os_compatibility.jsonl', 'entity' => 'os_compatibility', 'handler' => 'import_handle_os_compat'],
        ['file' => 'evidence.jsonl', 'entity' => 'evidence', 'handler' => 'import_handle_evidence'],
        ['file' => 'articles.jsonl', 'entity' => 'article', 'handler' => 'import_handle_article'],
    ];

    $adminUser = db()->query("SELECT id FROM users ORDER BY id LIMIT 1")->fetch();
    $ctx = [
        'modelsMap' => $modelsMap,
        'sourcesMap' => $sourcesMap,
        'boardsMap' => $boardsMap,
        'manufacturerId' => $manufacturersMap['apple'] ?? null,
        'userId' => $adminUser ? (int) $adminUser['id'] : 0,
    ];

    foreach ($dependentSpecs as $spec) {
        $doneMap = import_load_key_map($runId, $spec['entity']);
        $i = 0;
        foreach (import_read_jsonl(STAGING_DIR . '/' . $spec['file']) as $row) {
            $key = $row['key'] ?? ($spec['entity'] . ':' . ($row['model_key'] ?? '') . ':' . $i++);
            if (isset($doneMap[$key])) {
                continue;
            }
            if ($validateOnly) {
                import_record($runId, $key, $spec['entity'], 'insert', null, 'done');
                continue;
            }
            if (!$commit) {
                $known = isset($ctx['modelsMap'][$row['model_key'] ?? '']);
                import_record($runId, $key, $spec['entity'], 'insert', null, $known ? 'done' : 'review');
                $summary[$known ? 'inserted' : 'review']++;
                continue;
            }
            try {
                $result = call_user_func($spec['handler'], $row, $ctx);
                if ($result['status'] === 'error') {
                    import_record($runId, $key, $spec['entity'], $result['action'], null, 'error', $result['error']);
                    $summary['errors']++;
                } else {
                    import_record($runId, $key, $spec['entity'], $result['action'], null, 'done', null, $result['id'] ?? null);
                    $summary[$result['action'] === 'insert' ? 'inserted' : 'updated']++;
                }
            } catch (Throwable $e) {
                import_record($runId, $key, $spec['entity'], 'insert', null, 'error', $e->getMessage());
                $summary['errors']++;
            }
        }
    }

    // ---------------------------------------------------------------
    // 6. Lacunas documentadas (ausência de esquema/boardview/dado)
    // ---------------------------------------------------------------
    foreach (import_read_jsonl(STAGING_DIR . '/missing_data.jsonl') as $i => $row) {
        $key = 'missing:' . $i;
        import_record($runId, $key, 'missing_data', 'skip', null, 'done');
    }

    knowledge_import_run_finish($runId, 'completed', $summary);
} catch (Throwable $e) {
    knowledge_import_run_finish($runId, 'failed', $summary + ['fatal' => $e->getMessage()]);
    fwrite(STDERR, 'ERRO FATAL: ' . $e->getMessage() . "\n");
    exit(1);
}

printf(
    "Run #%d concluído — inseridos: %d, atualizados: %d, ignorados: %d, revisão: %d, erros: %d\n",
    $runId, $summary['inserted'], $summary['updated'], $summary['skipped'], $summary['review'], $summary['errors']
);
