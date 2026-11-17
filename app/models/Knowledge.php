<?php
/** Model: Centro de Conhecimento */

const KNOWLEDGE_SECTION_LABELS = [
    'articles' => 'Artigos',
    'repairs' => 'Reparos',
    'schematics' => 'Esquemas Elétricos',
    'boardviews' => 'Boardviews',
    'files' => 'Arquivos',
    'photos' => 'Fotos',
    'videos' => 'Vídeos',
    'pdfs' => 'PDFs',
    'links' => 'Links',
    'datasheets' => 'Datasheets',
    'firmware' => 'Firmware',
    'favorites' => 'Favoritos',
    'history' => 'Histórico',
    'tags' => 'Tags',
];

const KNOWLEDGE_ARTICLE_STATUSES = ['rascunho', 'publicado', 'arquivado'];
const KNOWLEDGE_ARTICLE_TYPES = ['artigo','procedimento','diagnostico','datasheet','firmware','link','arquivo'];
const KNOWLEDGE_DIFFICULTIES = ['baixa','media','alta','avancada'];
const KNOWLEDGE_REPAIR_RESULTS = ['resolvido','parcial','sem_reparo','recorrente','aguardando_peca'];
const KNOWLEDGE_ASSET_CATEGORIES = ['arquivo','foto','video','pdf','boardview','link','datasheet','firmware','esquema','zip','documento'];

function knowledge_normalize_key(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $value = str_replace(
        ['Á','À','Â','Ã','Ä','á','à','â','ã','ä','É','È','Ê','Ë','é','è','ê','ë','Í','Ì','Î','Ï','í','ì','î','ï','Ó','Ò','Ô','Õ','Ö','ó','ò','ô','õ','ö','Ú','Ù','Û','Ü','ú','ù','û','ü','Ç','ç','Ñ','ñ'],
        ['A','A','A','A','A','a','a','a','a','a','E','E','E','E','e','e','e','e','I','I','I','I','i','i','i','i','O','O','O','O','O','o','o','o','o','o','U','U','U','U','u','u','u','u','C','c','N','n'],
        $value
    );
    return strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $value) ?? '');
}

function knowledge_slugify(string $value): string
{
    $value = str_replace(
        ['Á','À','Â','Ã','Ä','á','à','â','ã','ä','É','È','Ê','Ë','é','è','ê','ë','Í','Ì','Î','Ï','í','ì','î','ï','Ó','Ò','Ô','Õ','Ö','ó','ò','ô','õ','ö','Ú','Ù','Û','Ü','ú','ù','û','ü','Ç','ç','Ñ','ñ'],
        ['A','A','A','A','A','a','a','a','a','a','E','E','E','E','e','e','e','e','I','I','I','I','i','i','i','i','O','O','O','O','O','o','o','o','o','o','U','U','U','U','u','u','u','u','C','c','N','n'],
        $value
    );
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '', '-'));
    return $slug !== '' ? $slug : 'item';
}

function knowledge_manufacturers_all(): array
{
    return db()->query('SELECT * FROM knowledge_manufacturers ORDER BY name')->fetchAll();
}

function knowledge_manufacturers_search(?string $search = null): array
{
    $sql = 'SELECT kmf.*,
                   (SELECT COUNT(*) FROM knowledge_models kmo WHERE kmo.manufacturer_id = kmf.id) AS models_count
            FROM knowledge_manufacturers kmf WHERE 1 = 1';
    $params = [];
    if ($search) {
        $sql .= ' AND (kmf.name ILIKE :q OR kmf.slug ILIKE :q OR kmf.normalized_name = :normalized)';
        $params['q'] = '%' . $search . '%';
        $params['normalized'] = knowledge_normalize_key($search);
    }
    $sql .= ' ORDER BY kmf.name LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function knowledge_manufacturer_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_manufacturers WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_manufacturer_create(array $data): int
{
    $name = trim((string) $data['name']);
    $normalized = knowledge_normalize_key($name);
    $existing = knowledge_manufacturer_by_normalized($normalized);
    if ($existing) {
        throw new RuntimeException('Fabricante já cadastrado.');
    }
    $slug = knowledge_unique_manufacturer_slug($name);
    $stmt = db()->prepare(
        'INSERT INTO knowledge_manufacturers (name, slug, normalized_name)
         VALUES (?, ?, ?) RETURNING id'
    );
    $stmt->execute([$name, $slug, $normalized]);
    return (int) $stmt->fetchColumn();
}

function knowledge_manufacturer_update(int $id, array $data): void
{
    $name = trim((string) $data['name']);
    $normalized = knowledge_normalize_key($name);
    $existing = knowledge_manufacturer_by_normalized($normalized);
    if ($existing && (int) $existing['id'] !== $id) {
        throw new RuntimeException('Fabricante já cadastrado.');
    }
    $stmt = db()->prepare('UPDATE knowledge_manufacturers SET name = ?, slug = ?, normalized_name = ? WHERE id = ?');
    $stmt->execute([$name, knowledge_unique_manufacturer_slug($name, $id), $normalized, $id]);
}

function knowledge_manufacturer_by_normalized(string $normalized): ?array
{
    if ($normalized === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM knowledge_manufacturers WHERE normalized_name = ? LIMIT 1');
    $stmt->execute([$normalized]);
    return $stmt->fetch() ?: null;
}

function knowledge_unique_manufacturer_slug(string $name, ?int $ignoreId = null): string
{
    $base = knowledge_slugify($name);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT id FROM knowledge_manufacturers WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function knowledge_model_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT km.*, mf.name AS manufacturer_name
         FROM knowledge_models km
         JOIN knowledge_manufacturers mf ON mf.id = km.manufacturer_id
         WHERE km.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_model_create(array $data): int
{
    if (!knowledge_manufacturer_find((int) $data['manufacturer_id'])) {
        throw new RuntimeException('Fabricante inválido.');
    }
    $normalizedName = knowledge_normalize_key($data['name']);
    $normalizedCode = knowledge_normalize_key($data['code'] ?? '');
    if (knowledge_model_duplicate((int) $data['manufacturer_id'], $normalizedName, $normalizedCode)) {
        throw new RuntimeException('Modelo já cadastrado para este fabricante.');
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_models
            (manufacturer_id, parent_id, name, code, description, frequent_issues, symptoms,
             diagnosis, procedures, comments, observations, tags, normalized_name, normalized_code,
             family, release_year, release_period, architecture, confidence_level)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['manufacturer_id'], $data['parent_id'] ?? null, trim((string) $data['name']),
        trim((string) ($data['code'] ?? '')) ?: null, $data['description'] ?? null,
        $data['frequent_issues'] ?? null, $data['symptoms'] ?? null, $data['diagnosis'] ?? null,
        $data['procedures'] ?? null, $data['comments'] ?? null, $data['observations'] ?? null,
        $data['tags'] ?? null, $normalizedName, $normalizedCode,
        $data['family'] ?? null, $data['release_year'] ?? null, $data['release_period'] ?? null,
        $data['architecture'] ?? null, knowledge_confidence($data['confidence_level'] ?? null),
    ]);
    return (int) $stmt->fetchColumn();
}

function knowledge_model_update(int $id, array $data): void
{
    if (!knowledge_manufacturer_find((int) $data['manufacturer_id'])) {
        throw new RuntimeException('Fabricante inválido.');
    }
    $normalizedName = knowledge_normalize_key($data['name']);
    $normalizedCode = knowledge_normalize_key($data['code'] ?? '');
    if (knowledge_model_duplicate((int) $data['manufacturer_id'], $normalizedName, $normalizedCode, $id)) {
        throw new RuntimeException('Modelo já cadastrado para este fabricante.');
    }
    $stmt = db()->prepare(
        'UPDATE knowledge_models
         SET manufacturer_id = ?, parent_id = ?, name = ?, code = ?, description = ?,
             frequent_issues = ?, symptoms = ?, diagnosis = ?, procedures = ?, comments = ?,
             observations = ?, tags = ?, normalized_name = ?, normalized_code = ?,
             family = ?, release_year = ?, release_period = ?, architecture = ?, confidence_level = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['manufacturer_id'], $data['parent_id'] ?? null, trim((string) $data['name']),
        trim((string) ($data['code'] ?? '')) ?: null, $data['description'] ?? null,
        $data['frequent_issues'] ?? null, $data['symptoms'] ?? null, $data['diagnosis'] ?? null,
        $data['procedures'] ?? null, $data['comments'] ?? null, $data['observations'] ?? null,
        $data['tags'] ?? null, $normalizedName, $normalizedCode,
        $data['family'] ?? null, $data['release_year'] ?? null, $data['release_period'] ?? null,
        $data['architecture'] ?? null, knowledge_confidence($data['confidence_level'] ?? null), $id,
    ]);
}

/**
 * Checa duplicidade só pelo nome normalizado — o código (A-number) sozinho NÃO decide duplicidade,
 * porque o mesmo Axxxx pode representar mais de um lançamento oficial da Apple.
 */
function knowledge_model_duplicate(int $manufacturerId, string $normalizedName, string $normalizedCode = '', ?int $ignoreId = null): bool
{
    $sql = 'SELECT id FROM knowledge_models WHERE manufacturer_id = :manufacturer_id AND normalized_name = :normalized_name';
    $params = ['manufacturer_id' => $manufacturerId, 'normalized_name' => $normalizedName];
    if ($ignoreId) {
        $sql .= ' AND id <> :ignore_id';
        $params['ignore_id'] = $ignoreId;
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}

function knowledge_models_all(?string $search = null, ?int $manufacturerId = null): array
{
    $sql = 'SELECT km.*, mf.name AS manufacturer_name
            FROM knowledge_models km
            JOIN knowledge_manufacturers mf ON mf.id = km.manufacturer_id
            WHERE 1 = 1';
    $params = [];
    if ($search) {
        $sql .= " AND (km.search_vector @@ plainto_tsquery('simple', :qts)
                   OR km.name ILIKE :q OR km.code ILIKE :q OR km.tags ILIKE :q)";
        $params['qts'] = $search;
        $params['q'] = '%' . $search . '%';
    }
    if ($manufacturerId) {
        $sql .= ' AND km.manufacturer_id = :manufacturer_id';
        $params['manufacturer_id'] = $manufacturerId;
    }
    $sql .= ' ORDER BY mf.name, km.name, km.code LIMIT 80';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function knowledge_model_upsert_for_device(?string $brand, ?string $model, ?string $modelCode, ?string $category = null): ?int
{
    $name = trim((string) ($modelCode ?: $model ?: $category));
    if ($name === '') {
        return null;
    }
    $manufacturer = knowledge_manufacturer_by_name($brand ?: 'Outros') ?: knowledge_manufacturer_by_name('Outros');
    if (!$manufacturer) {
        return null;
    }
    $code = $modelCode ? strtoupper(trim($modelCode)) : null;
    $normalizedName = knowledge_normalize_key($name);
    $normalizedCode = knowledge_normalize_key($code);
    $stmt = db()->prepare(
        'SELECT id FROM knowledge_models
         WHERE manufacturer_id = ?
           AND (normalized_name = ? OR (? <> \'\' AND normalized_code = ?))
         LIMIT 1'
    );
    $stmt->execute([(int) $manufacturer['id'], $normalizedName, $normalizedCode, $normalizedCode]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_models (manufacturer_id, name, code, description, tags, normalized_name, normalized_code)
         VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        (int) $manufacturer['id'],
        $name,
        $code,
        trim((string) $category) ?: null,
        'os, importado',
        $normalizedName,
        $normalizedCode,
    ]);
    return (int) $stmt->fetchColumn();
}

function knowledge_manufacturer_by_name(string $name): ?array
{
    $normalized = knowledge_normalize_key($name);
    $stmt = db()->prepare('SELECT * FROM knowledge_manufacturers WHERE normalized_name = ? OR LOWER(name) = LOWER(?) LIMIT 1');
    $stmt->execute([$normalized, $name]);
    return $stmt->fetch() ?: null;
}

function knowledge_dashboard_stats(): array
{
    $pdo = db();
    return [
        'articles' => (int) $pdo->query('SELECT COUNT(*) FROM knowledge_articles')->fetchColumn(),
        'repairs' => (int) $pdo->query('SELECT COUNT(*) FROM knowledge_repairs')->fetchColumn(),
        'schematics' => (int) $pdo->query('SELECT COUNT(*) FROM schematics')->fetchColumn(),
        'models' => (int) $pdo->query('SELECT COUNT(*) FROM knowledge_models')->fetchColumn(),
        'manufacturers' => (int) $pdo->query('SELECT COUNT(*) FROM knowledge_manufacturers')->fetchColumn(),
        'assets' => (int) $pdo->query('SELECT COUNT(*) FROM knowledge_assets')->fetchColumn(),
    ];
}

function knowledge_latest_items(int $limit = 8): array
{
    $stmt = db()->prepare(
        "(SELECT 'artigo' AS kind, id, title, created_at, '/knowledge/articles/view?id=' || id AS url FROM knowledge_articles)
         UNION ALL
         (SELECT 'reparo' AS kind, id, title, created_at, '/knowledge/repairs/view?id=' || id AS url FROM knowledge_repairs)
         UNION ALL
         (SELECT 'esquema' AS kind, id, title, created_at, '/knowledge/schematics/view?id=' || id AS url FROM schematics)
         ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->bindValue(1, max(1, min($limit, 20)), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function knowledge_popular_articles(int $limit = 6): array
{
    $stmt = db()->prepare(
        'SELECT * FROM knowledge_articles
         WHERE status <> ?
         ORDER BY views_count DESC, searches_count DESC, updated_at DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, 'arquivado');
    $stmt->bindValue(2, max(1, min($limit, 20)), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function knowledge_search(string $query, int $limit = 30, array $filters = []): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }
    $limit = max(1, min($limit, 60));
    $where = [];
    $params = ['qts' => $query, 'q' => '%' . $query . '%'];

    if (!empty($filters['type'])) {
        $where[] = 'type = :type';
        $params['type'] = $filters['type'];
    }
    if (!empty($filters['manufacturer_id'])) {
        $where[] = 'manufacturer_id = :manufacturer_id';
        $params['manufacturer_id'] = (int) $filters['manufacturer_id'];
    }
    if (!empty($filters['model_id'])) {
        $where[] = 'model_id = :model_id';
        $params['model_id'] = (int) $filters['model_id'];
    }
    if (!empty($filters['category'])) {
        $where[] = 'category = :category';
        $params['category'] = $filters['category'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'status = :status';
        $params['status'] = $filters['status'];
    }
    if (!empty($filters['favorite'])) {
        $where[] = 'favorite = TRUE';
    }
    if (!empty($filters['period_from'])) {
        $where[] = 'updated_at >= :period_from';
        $params['period_from'] = $filters['period_from'];
    }
    $outerWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $stmt = db()->prepare(
        "SELECT * FROM (
            SELECT 'article' AS type, id, manufacturer_id, model_id, COALESCE(category, type) AS category, status, favorite,
                   title, summary AS snippet, tags, updated_at,
                   '/knowledge/articles/view?id=' || id AS url,
                   ts_rank(search_vector, plainto_tsquery('simple', :qts)) + 0.30 AS rank
            FROM knowledge_articles
            WHERE search_vector @@ plainto_tsquery('simple', :qts)
               OR title ILIKE :q OR slug ILIKE :q OR summary ILIKE :q OR symptoms ILIKE :q
               OR diagnosis ILIKE :q OR solution ILIKE :q OR procedure ILIKE :q
               OR content ILIKE :q OR tools ILIKE :q OR parts_used ILIKE :q OR tags ILIKE :q
            UNION ALL
            SELECT 'repair' AS type, id, manufacturer_id, model_id, 'reparo' AS category, status, FALSE AS favorite,
                   title, problem AS snippet, tags, updated_at,
                   '/knowledge/repairs/view?id=' || id AS url,
                   ts_rank(search_vector, plainto_tsquery('simple', :qts)) + 0.20 AS rank
            FROM knowledge_repairs
            WHERE search_vector @@ plainto_tsquery('simple', :qts)
               OR title ILIKE :q OR problem ILIKE :q OR symptoms ILIKE :q OR diagnosis ILIKE :q
               OR tests_performed ILIKE :q OR solution ILIKE :q OR parts ILIKE :q OR tools ILIKE :q OR tags ILIKE :q
            UNION ALL
            SELECT 'schematic' AS type, id, manufacturer_id, knowledge_model_id AS model_id, 'esquema' AS category, 'publicado' AS status, favorite,
                   title, notes AS snippet, tags, created_at AS updated_at,
                   '/knowledge/schematics/view?id=' || id AS url,
                   ts_rank(search_vector, plainto_tsquery('simple', :qts)) + 0.15 AS rank
            FROM schematics
            WHERE search_vector @@ plainto_tsquery('simple', :qts)
               OR title ILIKE :q OR model_code ILIKE :q OR board_code ILIKE :q OR notes ILIKE :q OR tags ILIKE :q
            UNION ALL
            SELECT 'model' AS type, km.id, km.manufacturer_id, km.id AS model_id, 'modelo' AS category, 'publicado' AS status, FALSE AS favorite,
                   mf.name || ' ' || COALESCE(km.code, km.name) AS title,
                   COALESCE(km.description, km.frequent_issues) AS snippet, km.tags, km.updated_at,
                   '/knowledge/models/view?id=' || km.id AS url,
                   ts_rank(km.search_vector, plainto_tsquery('simple', :qts)) + 0.10 AS rank
            FROM knowledge_models km
            JOIN knowledge_manufacturers mf ON mf.id = km.manufacturer_id
            WHERE km.search_vector @@ plainto_tsquery('simple', :qts)
               OR km.name ILIKE :q OR km.code ILIKE :q OR km.frequent_issues ILIKE :q OR km.tags ILIKE :q
            UNION ALL
            SELECT 'manufacturer' AS type, id, id AS manufacturer_id, NULL AS model_id, 'fabricante' AS category, 'publicado' AS status, FALSE AS favorite,
                   name AS title, slug AS snippet, NULL AS tags, created_at AS updated_at,
                   '/knowledge/manufacturers/view?id=' || id AS url,
                   0.08 AS rank
            FROM knowledge_manufacturers
            WHERE name ILIKE :q OR slug ILIKE :q OR normalized_name = :normalized
            UNION ALL
            SELECT 'asset' AS type, ka.id, COALESCE(ka.manufacturer_id, km.manufacturer_id) AS manufacturer_id,
                   ka.model_id, ka.category, 'publicado' AS status, FALSE AS favorite,
                   ka.title, COALESCE(ka.description, ka.original_name, ka.external_link) AS snippet, ka.tags, ka.uploaded_at AS updated_at,
                   '/knowledge/assets/view?id=' || ka.id AS url,
                   0.06 AS rank
            FROM knowledge_assets ka
            LEFT JOIN knowledge_models km ON km.id = ka.model_id
            WHERE ka.title ILIKE :q OR ka.description ILIKE :q OR ka.original_name ILIKE :q
               OR ka.external_link ILIKE :q OR ka.tags ILIKE :q OR ka.category ILIKE :q
            UNION ALL
            SELECT 'identifier' AS type, ki.id, km2.manufacturer_id, ki.model_id, 'identificador' AS category,
                   'publicado' AS status, FALSE AS favorite,
                   mf2.name || ' ' || COALESCE(km2.code, km2.name) || ' — ' || ki.identifier_type AS title,
                   ki.identifier_value AS snippet, NULL AS tags, ki.updated_at,
                   '/knowledge/models/view?id=' || km2.id AS url,
                   0.12 AS rank
            FROM knowledge_model_identifiers ki
            JOIN knowledge_models km2 ON km2.id = ki.model_id
            JOIN knowledge_manufacturers mf2 ON mf2.id = km2.manufacturer_id
            WHERE ki.identifier_value ILIKE :q OR ki.identifier_type ILIKE :q
            UNION ALL
            SELECT 'logic_board' AS type, lb.id, NULL AS manufacturer_id, NULL AS model_id, 'placa' AS category,
                   'publicado' AS status, FALSE AS favorite,
                   COALESCE(lb.full_code, lb.board_code) AS title, lb.description AS snippet, NULL AS tags, lb.updated_at,
                   '/knowledge/boards/view?id=' || lb.id AS url,
                   0.10 AS rank
            FROM knowledge_logic_boards lb
            WHERE lb.board_code ILIKE :q OR lb.full_code ILIKE :q OR lb.board_id ILIKE :q
            UNION ALL
            SELECT 'source' AS type, ks.id, NULL AS manufacturer_id, NULL AS model_id, 'fonte' AS category,
                   'publicado' AS status, FALSE AS favorite,
                   ks.title, ks.organization AS snippet, NULL AS tags, ks.created_at AS updated_at,
                   '/knowledge/sources' AS url,
                   0.05 AS rank
            FROM knowledge_sources ks
            WHERE ks.title ILIKE :q OR ks.organization ILIKE :q
        ) s" . $outerWhere . " ORDER BY rank DESC, updated_at DESC LIMIT :limit"
    );
    $stmt->bindValue('qts', $query);
    $stmt->bindValue('q', '%' . $query . '%');
    $stmt->bindValue('normalized', knowledge_normalize_key($query));
    foreach ($params as $key => $value) {
        if (in_array($key, ['qts', 'q'], true)) {
            continue;
        }
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();
    knowledge_search_log($query, $filters, count($results));
    return $results;
}

function knowledge_search_log(string $query, array $filters, int $resultsCount): void
{
    $user = current_user();
    $stmt = db()->prepare(
        'INSERT INTO knowledge_search_logs (user_id, query, filters, results_count)
         VALUES (?, ?, ?::jsonb, ?)'
    );
    $stmt->execute([
        $user['id'] ?? null,
        mb_substr($query, 0, 500),
        json_encode($filters, JSON_UNESCAPED_UNICODE),
        $resultsCount,
    ]);
}

function knowledge_recent_searches(int $limit = 8): array
{
    $stmt = db()->prepare(
        'SELECT query, MAX(created_at) AS last_at, COUNT(*) AS total
         FROM knowledge_search_logs
         GROUP BY query
         ORDER BY last_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, max(1, min($limit, 20)), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function knowledge_frequent_searches(int $limit = 8): array
{
    $stmt = db()->prepare(
        'SELECT query, COUNT(*) AS total
         FROM knowledge_search_logs
         GROUP BY query
         ORDER BY total DESC, MAX(created_at) DESC LIMIT ?'
    );
    $stmt->bindValue(1, max(1, min($limit, 20)), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function knowledge_related_for_device(array $device): array
{
    $brand = trim((string) ($device['brand'] ?? ''));
    $modelCode = trim((string) ($device['model_code'] ?? ''));
    $model = trim((string) ($device['model'] ?? ''));
    $category = trim((string) ($device['category'] ?? ''));
    $terms = array_values(array_filter([$brand, $modelCode, $model, $category]));
    if (!$terms) {
        return ['articles' => 0, 'repairs' => 0, 'schematics' => 0, 'boardviews' => 0, 'datasheets' => 0, 'query' => ''];
    }
    $query = implode(' ', array_slice($terms, 0, 4));
    $likeTerms = array_map(static fn ($term) => '%' . $term . '%', $terms);

    $counts = ['articles' => 0, 'repairs' => 0, 'schematics' => 0, 'boardviews' => 0, 'datasheets' => 0, 'query' => $query];

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM schematics
         WHERE (:model_code <> '' AND UPPER(model_code) = UPPER(:model_code))
            OR title ILIKE :q OR notes ILIKE :q OR device_category ILIKE :q"
    );
    $stmt->execute(['model_code' => $modelCode, 'q' => '%' . $query . '%']);
    $counts['schematics'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM knowledge_articles
         WHERE status <> 'arquivado'
           AND (title ILIKE :q OR summary ILIKE :q OR content ILIKE :q OR tags ILIKE :q)"
    );
    $stmt->execute(['q' => '%' . $query . '%']);
    $counts['articles'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM knowledge_repairs
         WHERE status <> 'arquivado'
           AND (title ILIKE :q OR problem ILIKE :q OR diagnosis ILIKE :q OR solution ILIKE :q OR tags ILIKE :q)"
    );
    $stmt->execute(['q' => '%' . $query . '%']);
    $counts['repairs'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM knowledge_assets
         WHERE category = 'boardview' AND (title ILIKE :q OR tags ILIKE :q)"
    );
    $stmt->execute(['q' => '%' . $query . '%']);
    $counts['boardviews'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM knowledge_assets
         WHERE category = 'datasheet' AND (title ILIKE :q OR tags ILIKE :q)"
    );
    $stmt->execute(['q' => '%' . $query . '%']);
    $counts['datasheets'] = (int) $stmt->fetchColumn();

    return $counts;
}

function knowledge_articles_all(array $filters = []): array
{
    $userId = (int) (current_user()['id'] ?? 0);
    $sql = 'SELECT ka.*, mf.name AS manufacturer_name, km.name AS model_name, km.code AS model_code,
                   EXISTS (
                       SELECT 1 FROM knowledge_favorites kf
                       WHERE kf.user_id = :user_id AND kf.target_type = \'article\' AND kf.target_id = ka.id
                   ) AS user_favorite
            FROM knowledge_articles ka
            LEFT JOIN knowledge_manufacturers mf ON mf.id = ka.manufacturer_id
            LEFT JOIN knowledge_models km ON km.id = ka.model_id
            WHERE 1 = 1';
    $params = ['user_id' => $userId];
    if (!empty($filters['q'])) {
        $sql .= " AND (ka.search_vector @@ plainto_tsquery('simple', :qts)
                   OR ka.title ILIKE :q OR ka.slug ILIKE :q OR ka.summary ILIKE :q OR ka.symptoms ILIKE :q
                   OR ka.diagnosis ILIKE :q OR ka.solution ILIKE :q OR ka.procedure ILIKE :q
                   OR ka.content ILIKE :q OR ka.tools ILIKE :q OR ka.parts_used ILIKE :q OR ka.tags ILIKE :q)";
        $params['qts'] = $filters['q'];
        $params['q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['status'])) {
        $sql .= ' AND ka.status = :status';
        $params['status'] = $filters['status'];
    }
    if (!empty($filters['manufacturer_id'])) {
        $sql .= ' AND ka.manufacturer_id = :manufacturer_id';
        $params['manufacturer_id'] = (int) $filters['manufacturer_id'];
    }
    if (!empty($filters['model_id'])) {
        $sql .= ' AND ka.model_id = :model_id';
        $params['model_id'] = (int) $filters['model_id'];
    }
    if (!empty($filters['category'])) {
        $sql .= ' AND ka.category = :category';
        $params['category'] = $filters['category'];
    }
    if (!empty($filters['favorite'])) {
        $sql .= ' AND EXISTS (
            SELECT 1 FROM knowledge_favorites kf
            WHERE kf.user_id = :favorite_user_id AND kf.target_type = \'article\' AND kf.target_id = ka.id
        )';
        $params['favorite_user_id'] = $userId;
    }
    $sql .= ' ORDER BY ka.updated_at DESC LIMIT 80';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function knowledge_article_find(int $id): ?array
{
    $userId = (int) (current_user()['id'] ?? 0);
    $stmt = db()->prepare(
        'SELECT ka.*, mf.name AS manufacturer_name, km.name AS model_name, km.code AS model_code,
                EXISTS (
                    SELECT 1 FROM knowledge_favorites kf
                    WHERE kf.user_id = :user_id AND kf.target_type = \'article\' AND kf.target_id = ka.id
                ) AS user_favorite
         FROM knowledge_articles ka
         LEFT JOIN knowledge_manufacturers mf ON mf.id = ka.manufacturer_id
         LEFT JOIN knowledge_models km ON km.id = ka.model_id
         WHERE ka.id = :id'
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    return $stmt->fetch() ?: null;
}

function knowledge_article_defaults(array $data): array
{
    $rawStatus = $data['status'] ?? 'rascunho';
    $rawType = $data['type'] ?? 'artigo';
    $rawDifficulty = $data['difficulty'] ?? 'media';
    $status = in_array($rawStatus, KNOWLEDGE_ARTICLE_STATUSES, true) ? $rawStatus : 'rascunho';
    $type = in_array($rawType, KNOWLEDGE_ARTICLE_TYPES, true) ? $rawType : 'artigo';
    $difficulty = in_array($rawDifficulty, KNOWLEDGE_DIFFICULTIES, true) ? $rawDifficulty : 'media';
    return array_merge([
        'manufacturer_id' => null,
        'model_id' => null,
        'title' => '',
        'slug' => '',
        'type' => $type,
        'status' => $status,
        'category' => null,
        'summary' => null,
        'symptoms' => null,
        'diagnosis' => null,
        'procedure' => null,
        'solution' => null,
        'tools' => null,
        'parts_used' => null,
        'avg_time_minutes' => null,
        'difficulty' => $difficulty,
        'notes' => null,
        'content' => null,
        'tags' => null,
        'favorite' => false,
        'author_user_id' => null,
        'source_type' => null,
        'source_id' => null,
    ], $data, ['type' => $type, 'status' => $status, 'difficulty' => $difficulty]);
}

function knowledge_article_create(array $data): int
{
    $data = knowledge_article_defaults($data);
    $slug = knowledge_unique_article_slug($data['slug'] ?: $data['title']);
    $stmt = db()->prepare(
        'INSERT INTO knowledge_articles
            (manufacturer_id, model_id, title, slug, type, status, category, summary, symptoms,
             diagnosis, procedure, solution, tools, parts_used, avg_time_minutes, difficulty,
             notes, content, tags, favorite, author_user_id, source_type, source_id, published_at, archived_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                 CASE WHEN ? = \'publicado\' THEN NOW() ELSE NULL END,
                 CASE WHEN ? = \'arquivado\' THEN NOW() ELSE NULL END)
         RETURNING id'
    );
    $stmt->execute([
        $data['manufacturer_id'], $data['model_id'], $data['title'], $slug, $data['type'],
        $data['status'], $data['category'], $data['summary'], $data['symptoms'],
        $data['diagnosis'], $data['procedure'], $data['solution'], $data['tools'],
        $data['parts_used'], $data['avg_time_minutes'], $data['difficulty'], $data['notes'],
        $data['content'], $data['tags'], !empty($data['favorite']) ? 'true' : 'false',
        $data['author_user_id'], $data['source_type'] ?? null, $data['source_id'] ?? null,
        $data['status'], $data['status'],
    ]);
    $id = (int) $stmt->fetchColumn();
    knowledge_article_history_record($id, (int) $data['author_user_id'], 'created', null, knowledge_article_snapshot($id));
    return $id;
}

function knowledge_article_update(int $id, array $data): void
{
    $data = knowledge_article_defaults($data);
    $before = knowledge_article_snapshot($id);
    $slug = knowledge_unique_article_slug($data['slug'] ?: $data['title'], $id);
    $stmt = db()->prepare(
        'UPDATE knowledge_articles
         SET manufacturer_id = ?, model_id = ?, title = ?, slug = ?, type = ?, status = ?,
             category = ?, summary = ?, symptoms = ?, diagnosis = ?, procedure = ?, solution = ?,
             tools = ?, parts_used = ?, avg_time_minutes = ?, difficulty = ?, notes = ?,
             content = ?, tags = ?, favorite = ?,
             published_at = CASE WHEN ? = \'publicado\' THEN COALESCE(published_at, NOW()) ELSE published_at END,
             archived_at = CASE WHEN ? = \'arquivado\' THEN COALESCE(archived_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $stmt->execute([
        $data['manufacturer_id'], $data['model_id'], $data['title'], $slug, $data['type'],
        $data['status'], $data['category'], $data['summary'], $data['symptoms'],
        $data['diagnosis'], $data['procedure'], $data['solution'], $data['tools'],
        $data['parts_used'], $data['avg_time_minutes'], $data['difficulty'], $data['notes'],
        $data['content'], $data['tags'], !empty($data['favorite']) ? 'true' : 'false',
        $data['status'], $data['status'], $id,
    ]);
    knowledge_article_history_record($id, (int) $data['author_user_id'], 'updated', $before, knowledge_article_snapshot($id));
}

function knowledge_article_archive(int $id, int $userId): void
{
    $before = knowledge_article_snapshot($id);
    $stmt = db()->prepare('UPDATE knowledge_articles SET status = ?, archived_at = NOW(), updated_at = NOW() WHERE id = ?');
    $stmt->execute(['arquivado', $id]);
    knowledge_article_history_record($id, $userId, 'archived', $before, knowledge_article_snapshot($id));
}

function knowledge_article_delete(int $id, int $userId = 0): void
{
    $before = knowledge_article_snapshot($id);
    $stmt = db()->prepare('UPDATE knowledge_articles SET status = ?, archived_at = NOW(), updated_at = NOW() WHERE id = ?');
    $stmt->execute(['arquivado', $id]);
    if ($before) {
        knowledge_article_history_record($id, $userId, 'deleted', $before, knowledge_article_snapshot($id));
    }
}

function knowledge_article_restore(int $id, int $userId): void
{
    $before = knowledge_article_snapshot($id);
    $stmt = db()->prepare('UPDATE knowledge_articles SET status = ?, archived_at = NULL, updated_at = NOW() WHERE id = ?');
    $stmt->execute(['rascunho', $id]);
    knowledge_article_history_record($id, $userId, 'restored', $before, knowledge_article_snapshot($id));
}

function knowledge_article_increment_view(int $id): void
{
    $stmt = db()->prepare('UPDATE knowledge_articles SET views_count = views_count + 1 WHERE id = ?');
    $stmt->execute([$id]);
}

function knowledge_article_snapshot(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_articles WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_article_history_record(int $articleId, ?int $userId, string $type, ?array $before, ?array $after): void
{
    $stmt = db()->prepare(
        'INSERT INTO knowledge_article_history (article_id, user_id, change_type, previous_version, new_version)
         VALUES (?, ?, ?, ?::jsonb, ?::jsonb)'
    );
    $stmt->execute([
        $articleId,
        $userId ?: null,
        $type,
        $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
        $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
    ]);
}

function knowledge_article_history_record_deleted(int $articleId, ?int $userId, array $before): void
{
    $stmt = db()->prepare(
        'INSERT INTO knowledge_article_history (article_id, user_id, change_type, previous_version, new_version)
         VALUES (?, ?, ?, ?::jsonb, NULL)'
    );
    $stmt->execute([$articleId, $userId ?: null, 'deleted', json_encode($before, JSON_UNESCAPED_UNICODE)]);
}

function knowledge_article_history_all(int $articleId): array
{
    $stmt = db()->prepare(
        'SELECT kh.*, u.name AS user_name
         FROM knowledge_article_history kh
         LEFT JOIN users u ON u.id = kh.user_id
         WHERE kh.article_id = ?
         ORDER BY kh.created_at DESC'
    );
    $stmt->execute([$articleId]);
    return $stmt->fetchAll();
}

function knowledge_article_history_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT kh.*, ka.author_user_id, ka.status
         FROM knowledge_article_history kh
         LEFT JOIN knowledge_articles ka ON ka.id = kh.article_id
         WHERE kh.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_article_restore_history(int $historyId, int $userId): int
{
    $history = knowledge_article_history_find($historyId);
    if (!$history || empty($history['previous_version'])) {
        throw new RuntimeException('Versão anterior indisponível.');
    }
    $data = json_decode($history['previous_version'], true);
    if (!is_array($data)) {
        throw new RuntimeException('Versão anterior inválida.');
    }
    $articleId = (int) $history['article_id'];
    $before = knowledge_article_snapshot($articleId);
    $stmt = db()->prepare(
        'UPDATE knowledge_articles
         SET manufacturer_id = ?, model_id = ?, title = ?, slug = ?, type = ?, status = ?,
             category = ?, summary = ?, symptoms = ?, diagnosis = ?, procedure = ?, solution = ?,
             tools = ?, parts_used = ?, avg_time_minutes = ?, difficulty = ?, notes = ?,
             content = ?, tags = ?, favorite = ?, published_at = ?, archived_at = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['manufacturer_id'] ?? null, $data['model_id'] ?? null, $data['title'] ?? '',
        knowledge_unique_article_slug($data['slug'] ?? ($data['title'] ?? 'artigo'), $articleId),
        $data['type'] ?? 'artigo', $data['status'] ?? 'rascunho', $data['category'] ?? null,
        $data['summary'] ?? null, $data['symptoms'] ?? null, $data['diagnosis'] ?? null,
        $data['procedure'] ?? null, $data['solution'] ?? null, $data['tools'] ?? null,
        $data['parts_used'] ?? null, $data['avg_time_minutes'] ?? null, $data['difficulty'] ?? 'media',
        $data['notes'] ?? null, $data['content'] ?? null, $data['tags'] ?? null,
        !empty($data['favorite']) ? 'true' : 'false', $data['published_at'] ?? null, $data['archived_at'] ?? null,
        $articleId,
    ]);
    knowledge_article_history_record($articleId, $userId, 'restored_version', $before, knowledge_article_snapshot($articleId));
    return $articleId;
}

function knowledge_unique_article_slug(string $seed, ?int $ignoreId = null): string
{
    $base = mb_substr(knowledge_slugify($seed), 0, 190);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT id FROM knowledge_articles WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function knowledge_repairs_all(array $filters = []): array
{
    $sql = 'SELECT kr.*, mf.name AS manufacturer_name, km.name AS model_name, km.code AS model_code,
                   c.name AS client_name, d.category AS device_category, d.model AS device_model
            FROM knowledge_repairs kr
            LEFT JOIN knowledge_manufacturers mf ON mf.id = kr.manufacturer_id
            LEFT JOIN knowledge_models km ON km.id = kr.model_id
            LEFT JOIN clients c ON c.id = kr.client_id
            LEFT JOIN devices d ON d.id = kr.device_id
            WHERE 1 = 1';
    $params = [];
    if (!empty($filters['q'])) {
        $sql .= " AND (kr.search_vector @@ plainto_tsquery('simple', :qts)
                   OR kr.title ILIKE :q OR kr.problem ILIKE :q OR kr.symptoms ILIKE :q
                   OR kr.diagnosis ILIKE :q OR kr.tests_performed ILIKE :q OR kr.solution ILIKE :q
                   OR kr.parts ILIKE :q OR kr.tools ILIKE :q OR kr.tags ILIKE :q)";
        $params['qts'] = $filters['q'];
        $params['q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['status'])) {
        $sql .= ' AND kr.status = :status';
        $params['status'] = $filters['status'];
    }
    if (!empty($filters['manufacturer_id'])) {
        $sql .= ' AND kr.manufacturer_id = :manufacturer_id';
        $params['manufacturer_id'] = (int) $filters['manufacturer_id'];
    }
    if (!empty($filters['model_id'])) {
        $sql .= ' AND kr.model_id = :model_id';
        $params['model_id'] = (int) $filters['model_id'];
    }
    $sql .= ' ORDER BY kr.updated_at DESC LIMIT 80';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function knowledge_repair_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT kr.*, mf.name AS manufacturer_name, km.name AS model_name, km.code AS model_code,
                c.name AS client_name, d.category AS device_category, d.model AS device_model
         FROM knowledge_repairs kr
         LEFT JOIN knowledge_manufacturers mf ON mf.id = kr.manufacturer_id
         LEFT JOIN knowledge_models km ON km.id = kr.model_id
         LEFT JOIN clients c ON c.id = kr.client_id
         LEFT JOIN devices d ON d.id = kr.device_id
         WHERE kr.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_repair_create(array $data): int
{
    $data = knowledge_repair_defaults($data);
    $stmt = db()->prepare(
        'INSERT INTO knowledge_repairs
            (manufacturer_id, model_id, service_order_id, client_id, device_id, title,
             problem, symptoms, diagnosis, tests_performed, solution, parts, tools, time_minutes,
             cost, observations, result, repair_date, tags, status, author_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['manufacturer_id'], $data['model_id'], $data['service_order_id'],
        $data['client_id'], $data['device_id'], $data['title'], $data['problem'],
        $data['symptoms'], $data['diagnosis'], $data['tests_performed'], $data['solution'],
        $data['parts'], $data['tools'], $data['time_minutes'], $data['cost'],
        $data['observations'], $data['result'], $data['repair_date'], $data['tags'],
        $data['status'], $data['author_user_id'],
    ]);
    return (int) $stmt->fetchColumn();
}

function knowledge_repair_update(int $id, array $data): void
{
    $data = knowledge_repair_defaults($data);
    $stmt = db()->prepare(
        'UPDATE knowledge_repairs
         SET manufacturer_id = ?, model_id = ?, service_order_id = ?, client_id = ?, device_id = ?,
             title = ?, problem = ?, symptoms = ?, diagnosis = ?, tests_performed = ?, solution = ?,
             parts = ?, tools = ?, time_minutes = ?, cost = ?, observations = ?, result = ?,
             repair_date = ?, tags = ?, status = ?,
             archived_at = CASE WHEN ? = \'arquivado\' THEN COALESCE(archived_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $stmt->execute([
        $data['manufacturer_id'], $data['model_id'], $data['service_order_id'],
        $data['client_id'], $data['device_id'], $data['title'], $data['problem'],
        $data['symptoms'], $data['diagnosis'], $data['tests_performed'], $data['solution'],
        $data['parts'], $data['tools'], $data['time_minutes'], $data['cost'],
        $data['observations'], $data['result'], $data['repair_date'], $data['tags'],
        $data['status'], $data['status'], $id,
    ]);
}

function knowledge_repair_defaults(array $data): array
{
    $rawStatus = $data['status'] ?? 'rascunho';
    $rawResult = $data['result'] ?? 'resolvido';
    $status = in_array($rawStatus, KNOWLEDGE_ARTICLE_STATUSES, true) ? $rawStatus : 'rascunho';
    $result = in_array($rawResult, KNOWLEDGE_REPAIR_RESULTS, true) ? $rawResult : 'resolvido';
    return array_merge([
        'manufacturer_id' => null,
        'model_id' => null,
        'service_order_id' => null,
        'client_id' => null,
        'device_id' => null,
        'title' => '',
        'problem' => null,
        'symptoms' => null,
        'diagnosis' => null,
        'tests_performed' => null,
        'solution' => null,
        'parts' => null,
        'tools' => null,
        'time_minutes' => null,
        'cost' => 0,
        'observations' => null,
        'result' => $result,
        'repair_date' => date('Y-m-d'),
        'tags' => null,
        'status' => $status,
        'author_user_id' => null,
    ], $data, ['status' => $status, 'result' => $result]);
}

function knowledge_repair_delete(int $id): void
{
    $stmt = db()->prepare('UPDATE knowledge_repairs SET status = ?, archived_at = NOW(), updated_at = NOW() WHERE id = ?');
    $stmt->execute(['arquivado', $id]);
}

function knowledge_repair_restore(int $id): void
{
    $stmt = db()->prepare('UPDATE knowledge_repairs SET status = ?, archived_at = NULL, updated_at = NOW() WHERE id = ?');
    $stmt->execute(['rascunho', $id]);
}

function knowledge_repair_convert_to_article(int $repairId, int $userId): int
{
    $repair = knowledge_repair_find($repairId);
    if (!$repair) {
        throw new RuntimeException('Reparo não encontrado.');
    }
    $existing = knowledge_article_from_source('repair', $repairId);
    if ($existing) {
        return (int) $existing['id'];
    }
    $content = "## Problema relatado\n" . trim((string) $repair['problem']) .
        "\n\n## Sintomas\n" . trim((string) $repair['symptoms']) .
        "\n\n## Diagnóstico\n" . trim((string) $repair['diagnosis']) .
        "\n\n## Testes realizados\n" . trim((string) $repair['tests_performed']) .
        "\n\n## Procedimento\n" . trim((string) $repair['solution']) .
        "\n\n## Peças utilizadas\n" . trim((string) $repair['parts']) .
        "\n\n## Ferramentas\n" . trim((string) $repair['tools']) .
        "\n\n## Observações\n" . trim((string) $repair['observations']);
    return knowledge_article_create([
        'manufacturer_id' => $repair['manufacturer_id'],
        'model_id' => $repair['model_id'],
        'title' => 'Artigo técnico — ' . $repair['title'],
        'type' => 'procedimento',
        'status' => 'rascunho',
        'category' => 'reparo',
        'summary' => $repair['problem'],
        'symptoms' => $repair['symptoms'],
        'diagnosis' => $repair['diagnosis'],
        'procedure' => $repair['solution'],
        'solution' => $repair['solution'],
        'tools' => $repair['tools'],
        'parts_used' => $repair['parts'],
        'avg_time_minutes' => $repair['time_minutes'],
        'notes' => $repair['observations'],
        'content' => $content,
        'tags' => $repair['tags'],
        'favorite' => false,
        'author_user_id' => $userId,
        'source_type' => 'repair',
        'source_id' => $repairId,
    ]);
}

function knowledge_article_from_source(string $sourceType, int $sourceId): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_articles WHERE source_type = ? AND source_id = ? LIMIT 1');
    $stmt->execute([$sourceType, $sourceId]);
    return $stmt->fetch() ?: null;
}

function knowledge_repair_from_order(array $order, int $userId): int
{
    $existing = knowledge_repair_from_order_id((int) $order['id']);
    if ($existing) {
        return (int) $existing['id'];
    }
    $manufacturerId = null;
    $modelId = knowledge_model_upsert_for_device($order['device_brand'] ?? 'Apple', $order['device_model'] ?? $order['quick_device_name'] ?? null, $order['model_code'] ?? null, $order['device_category'] ?? null);
    if ($modelId) {
        $model = knowledge_model_find($modelId);
        $manufacturerId = $model ? (int) $model['manufacturer_id'] : null;
    }
    $id = knowledge_repair_create([
        'manufacturer_id' => $manufacturerId,
        'model_id' => $modelId,
        'service_order_id' => (int) $order['id'],
        'client_id' => $order['client_id'] ? (int) $order['client_id'] : null,
        'device_id' => $order['device_id'] ? (int) $order['device_id'] : null,
        'title' => 'Reparo OS #' . $order['id'] . ' — ' . $order['title'],
        'problem' => $order['reported_issue'],
        'symptoms' => $order['reported_issue'],
        'diagnosis' => $order['diagnosis'],
        'tests_performed' => null,
        'solution' => $order['solution'],
        'parts' => null,
        'tools' => null,
        'time_minutes' => null,
        'cost' => (float) ($order['total_price'] ?? 0),
        'observations' => 'Rascunho criado automaticamente ao finalizar OS.' . (!empty($order['is_fast_order']) ? "\nFast OS: " . trim(($order['quick_customer_name'] ?? '') . ' / ' . ($order['quick_device_name'] ?? ''), ' /') : ''),
        'result' => 'resolvido',
        'repair_date' => date('Y-m-d'),
        'tags' => trim(($order['model_code'] ?? '') . ', os-' . $order['id'], ', '),
        'status' => 'rascunho',
        'author_user_id' => $userId,
    ]);
    knowledge_order_link((int) $order['id'], 'repair', $id);
    return $id;
}

function knowledge_article_draft_from_order(array $order, int $userId): int
{
    $existing = knowledge_article_from_source('service_order', (int) $order['id']);
    if ($existing) {
        knowledge_order_link((int) $order['id'], 'article', (int) $existing['id']);
        return (int) $existing['id'];
    }
    $manufacturerId = null;
    $modelId = knowledge_model_upsert_for_device($order['device_brand'] ?? 'Apple', $order['device_model'] ?? $order['quick_device_name'] ?? null, $order['model_code'] ?? null, $order['device_category'] ?? null);
    if ($modelId) {
        $model = knowledge_model_find($modelId);
        $manufacturerId = $model ? (int) $model['manufacturer_id'] : null;
    }
    $content = "## Problema\n" . trim((string) $order['reported_issue']) .
        "\n\n## Diagnóstico\n" . trim((string) $order['diagnosis']) .
        "\n\n## Solução\n" . trim((string) $order['solution']) .
        "\n\n## Peças\n\n\n## Tempo\n\n\n## Observações\nRascunho criado automaticamente a partir da OS #" . $order['id'] . "." .
        (!empty($order['is_fast_order']) ? "\n\nFast OS: " . trim(($order['quick_customer_name'] ?? '') . ' / ' . ($order['quick_device_name'] ?? ''), ' /') : '');
    $id = knowledge_article_create([
        'manufacturer_id' => $manufacturerId,
        'model_id' => $modelId,
        'title' => 'Rascunho técnico — OS #' . $order['id'] . ' — ' . $order['title'],
        'type' => 'procedimento',
        'status' => 'rascunho',
        'category' => $order['device_category'] ?? null,
        'summary' => $order['reported_issue'],
        'symptoms' => $order['reported_issue'],
        'diagnosis' => $order['diagnosis'],
        'procedure' => $order['solution'],
        'solution' => $order['solution'],
        'content' => $content,
        'tags' => trim(($order['model_code'] ?? '') . ', os-' . $order['id'], ', '),
        'favorite' => false,
        'author_user_id' => $userId,
        'source_type' => 'service_order',
        'source_id' => (int) $order['id'],
    ]);
    knowledge_order_link((int) $order['id'], 'article', $id);
    return $id;
}

function knowledge_repair_from_order_id(int $orderId): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_repairs WHERE service_order_id = ? ORDER BY id LIMIT 1');
    $stmt->execute([$orderId]);
    return $stmt->fetch() ?: null;
}

function knowledge_order_link(int $orderId, string $targetType, int $targetId): void
{
    $stmt = db()->prepare(
        'INSERT INTO knowledge_order_links (service_order_id, target_type, target_id)
         VALUES (?, ?, ?)
         ON CONFLICT (service_order_id, target_type, target_id) DO NOTHING'
    );
    $stmt->execute([$orderId, $targetType, $targetId]);
}

function knowledge_section_asset_category(string $section): ?string
{
    return match ($section) {
        'boardviews' => 'boardview',
        'files' => 'arquivo',
        'photos' => 'foto',
        'videos' => 'video',
        'pdfs' => 'pdf',
        'links' => 'link',
        'datasheets' => 'datasheet',
        'firmware' => 'firmware',
        default => null,
    };
}

function knowledge_assets_by_category(string $category, ?string $search = null): array
{
    $sql = 'SELECT ka.*, km.name AS model_name, km.code AS model_code,
                   COALESCE(mf_asset.name, mf_model.name) AS manufacturer_name
            FROM knowledge_assets ka
            LEFT JOIN knowledge_models km ON km.id = ka.model_id
            LEFT JOIN knowledge_manufacturers mf_asset ON mf_asset.id = ka.manufacturer_id
            LEFT JOIN knowledge_manufacturers mf_model ON mf_model.id = km.manufacturer_id
            WHERE ka.category = :category';
    $params = ['category' => $category];
    if ($search) {
        $sql .= ' AND (ka.title ILIKE :q OR ka.tags ILIKE :q OR ka.external_link ILIKE :q)';
        $params['q'] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY ka.created_at DESC LIMIT 80';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function knowledge_asset_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT ka.*, km.name AS model_name, km.code AS model_code,
                COALESCE(mf_asset.name, mf_model.name) AS manufacturer_name,
                art.title AS article_title, rep.title AS repair_title, u.name AS user_name
         FROM knowledge_assets ka
         LEFT JOIN knowledge_models km ON km.id = ka.model_id
         LEFT JOIN knowledge_manufacturers mf_asset ON mf_asset.id = ka.manufacturer_id
         LEFT JOIN knowledge_manufacturers mf_model ON mf_model.id = km.manufacturer_id
         LEFT JOIN knowledge_articles art ON art.id = ka.article_id
         LEFT JOIN knowledge_repairs rep ON rep.id = ka.repair_id
         LEFT JOIN users u ON u.id = ka.user_id
         WHERE ka.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_asset_by_hash(string $hash): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_assets WHERE file_hash = ? LIMIT 1');
    $stmt->execute([$hash]);
    return $stmt->fetch() ?: null;
}

function knowledge_asset_create(array $data): int
{
    $category = in_array($data['category'] ?? 'arquivo', KNOWLEDGE_ASSET_CATEGORIES, true) ? $data['category'] : 'arquivo';
    $stmt = db()->prepare(
        'INSERT INTO knowledge_assets
            (manufacturer_id, model_id, article_id, repair_id, schematic_id, category, title,
             original_name, stored_name, file_hash, file_path, storage_path, external_link,
             mime_type, size_bytes, tags, user_id, description, uploaded_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
         RETURNING id'
    );
    $stmt->execute([
        $data['manufacturer_id'] ?? null,
        $data['model_id'] ?? null,
        $data['article_id'] ?? null,
        $data['repair_id'] ?? null,
        $data['schematic_id'] ?? null,
        $category,
        $data['title'],
        $data['original_name'] ?? null,
        $data['stored_name'] ?? null,
        $data['file_hash'] ?? null,
        $data['file_path'] ?? null,
        $data['storage_path'] ?? null,
        $data['external_link'] ?? null,
        $data['mime_type'] ?? null,
        $data['size_bytes'] ?? null,
        $data['tags'] ?? null,
        $data['user_id'] ?? null,
        $data['description'] ?? null,
    ]);
    return (int) $stmt->fetchColumn();
}

function knowledge_asset_delete(int $id): void
{
    $stmt = db()->prepare('UPDATE knowledge_assets SET description = COALESCE(description, \'\') || E\'\\n[Arquivado]\' WHERE id = ?');
    $stmt->execute([$id]);
}

function knowledge_assets_for_model(int $modelId): array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_assets WHERE model_id = ? ORDER BY category, uploaded_at DESC');
    $stmt->execute([$modelId]);
    return $stmt->fetchAll();
}

function knowledge_favorite_toggle(int $userId, string $targetType, int $targetId): bool
{
    $stmt = db()->prepare('SELECT id FROM knowledge_favorites WHERE user_id = ? AND target_type = ? AND target_id = ?');
    $stmt->execute([$userId, $targetType, $targetId]);
    $id = $stmt->fetchColumn();
    if ($id) {
        db()->prepare('DELETE FROM knowledge_favorites WHERE id = ?')->execute([$id]);
        return false;
    }
    db()->prepare(
        'INSERT INTO knowledge_favorites (user_id, target_type, target_id)
         VALUES (?, ?, ?)
         ON CONFLICT (user_id, target_type, target_id) DO NOTHING'
    )->execute([$userId, $targetType, $targetId]);
    return true;
}

function knowledge_favorites_for_user(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT * FROM (
            SELECT 'article' AS type, ka.id, ka.title, ka.summary AS snippet, kf.created_at,
                   '/knowledge/articles/view?id=' || ka.id AS url
            FROM knowledge_favorites kf
            JOIN knowledge_articles ka ON ka.id = kf.target_id
            WHERE kf.user_id = :user_id AND kf.target_type = 'article'
            UNION ALL
            SELECT 'repair' AS type, kr.id, kr.title, kr.problem AS snippet, kf.created_at,
                   '/knowledge/repairs/view?id=' || kr.id AS url
            FROM knowledge_favorites kf
            JOIN knowledge_repairs kr ON kr.id = kf.target_id
            WHERE kf.user_id = :user_id AND kf.target_type = 'repair'
            UNION ALL
            SELECT 'asset' AS type, ka.id, ka.title, ka.description AS snippet, kf.created_at,
                   '/knowledge/assets/view?id=' || ka.id AS url
            FROM knowledge_favorites kf
            JOIN knowledge_assets ka ON ka.id = kf.target_id
            WHERE kf.user_id = :user_id AND kf.target_type = 'asset'
            UNION ALL
            SELECT 'model' AS type, km.id, km.name AS title, km.description AS snippet, kf.created_at,
                   '/knowledge/models/view?id=' || km.id AS url
            FROM knowledge_favorites kf
            JOIN knowledge_models km ON km.id = kf.target_id
            WHERE kf.user_id = :user_id AND kf.target_type = 'model'
        ) s ORDER BY created_at DESC LIMIT 100"
    );
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

// =============================================================
// Expansão: identificadores, variantes, placas lógicas, fontes,
// compatibilidade macOS, programas de serviço, importação.
// =============================================================

const KNOWLEDGE_CONFIDENCE_LEVELS = ['oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado'];
const KNOWLEDGE_IDENTIFIER_TYPES = ['model_number','model_identifier','emc','order_number','part_number','marketing_part_number','board_id','internal_identifier','serial_prefix'];
const KNOWLEDGE_VARIANT_CONFIG_TYPES = ['standard','cto','bto','regional','revision'];
const KNOWLEDGE_AVAILABILITY_STATUSES = ['available_official','available_licensed','external_reference_only','not_found','legally_unavailable','source_restricted','unverified','blocked_suspicious_source'];
const KNOWLEDGE_OS_COMPATIBILITY_TYPES = ['original','minimum','maximum_official','currently_supported','security_updates','boot_camp','unofficial_oclp'];

function knowledge_confidence(?string $value): string
{
    return in_array($value, KNOWLEDGE_CONFIDENCE_LEVELS, true) ? $value : 'nao_verificado';
}

/**
 * Chave de deduplicação de modelos: fabricante + nome + ano, com fallback por Model Identifier/EMC.
 * O A-number sozinho NÃO decide duplicidade — o mesmo Axxxx pode ser mais de um lançamento oficial.
 */
function knowledge_model_find_by_dedupe(int $manufacturerId, string $name, ?int $releaseYear, ?string $modelIdentifier = null, ?string $emc = null): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM knowledge_models
         WHERE manufacturer_id = ? AND normalized_name = ? AND (release_year IS NOT DISTINCT FROM ?)'
    );
    $stmt->execute([$manufacturerId, knowledge_normalize_key($name), $releaseYear]);
    $match = $stmt->fetch();
    if ($match) {
        return $match;
    }
    if (!$modelIdentifier && !$emc) {
        return null;
    }
    $stmt = db()->prepare(
        "SELECT km.* FROM knowledge_models km
         JOIN knowledge_model_identifiers ki ON ki.model_id = km.id
         WHERE km.manufacturer_id = ?
           AND ((ki.identifier_type = 'model_identifier' AND UPPER(ki.identifier_value) = UPPER(?))
             OR (ki.identifier_type = 'emc' AND UPPER(ki.identifier_value) = UPPER(?)))
         LIMIT 1"
    );
    $stmt->execute([$manufacturerId, (string) $modelIdentifier, (string) $emc]);
    return $stmt->fetch() ?: null;
}

function knowledge_model_identifiers_for_model(int $modelId): array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_model_identifiers WHERE model_id = ? ORDER BY identifier_type, is_primary DESC');
    $stmt->execute([$modelId]);
    return $stmt->fetchAll();
}

function knowledge_model_identifier_upsert(array $data): int
{
    $type = in_array($data['identifier_type'] ?? '', KNOWLEDGE_IDENTIFIER_TYPES, true) ? $data['identifier_type'] : 'model_number';
    $value = trim((string) $data['identifier_value']);
    $region = $data['region'] ?? null;
    $stmt = db()->prepare(
        "SELECT id FROM knowledge_model_identifiers
         WHERE model_id = ? AND identifier_type = ? AND UPPER(identifier_value) = UPPER(?) AND COALESCE(region, '') = COALESCE(?, '')"
    );
    $stmt->execute([$data['model_id'], $type, $value, $region]);
    $existingId = $stmt->fetchColumn();
    if ($existingId) {
        $stmt = db()->prepare(
            'UPDATE knowledge_model_identifiers
             SET is_primary = ?, confidence_level = ?, source_id = ?, notes = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            !empty($data['is_primary']) ? 'true' : 'false', knowledge_confidence($data['confidence_level'] ?? null),
            $data['source_id'] ?? null, $data['notes'] ?? null, $existingId,
        ]);
        return (int) $existingId;
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_model_identifiers (model_id, identifier_type, identifier_value, region, is_primary, confidence_level, source_id, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['model_id'], $type, $value, $region, !empty($data['is_primary']) ? 'true' : 'false',
        knowledge_confidence($data['confidence_level'] ?? null), $data['source_id'] ?? null, $data['notes'] ?? null,
    ]);
    return (int) $stmt->fetchColumn();
}

function knowledge_model_variants_for_model(int $modelId): array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_model_variants WHERE model_id = ? ORDER BY is_standard DESC, variant_name');
    $stmt->execute([$modelId]);
    return $stmt->fetchAll();
}

function knowledge_model_variant_upsert(array $data): int
{
    $type = in_array($data['configuration_type'] ?? '', KNOWLEDGE_VARIANT_CONFIG_TYPES, true) ? $data['configuration_type'] : 'standard';
    $fields = [
        'variant_name' => trim((string) $data['variant_name']),
        'configuration_type' => $type,
        'processor' => $data['processor'] ?? null,
        'cpu_cores' => $data['cpu_cores'] ?? null,
        'gpu' => $data['gpu'] ?? null,
        'gpu_cores' => $data['gpu_cores'] ?? null,
        'memory' => $data['memory'] ?? null,
        'storage' => $data['storage'] ?? null,
        'color' => $data['color'] ?? null,
        'part_number' => $data['part_number'] ?? null,
        'order_number' => $data['order_number'] ?? null,
        'region' => $data['region'] ?? null,
        'launch_price' => $data['launch_price'] ?? null,
        'currency' => $data['currency'] ?? null,
        'is_standard' => !empty($data['is_standard']) ? 'true' : 'false',
        'is_cto' => !empty($data['is_cto']) ? 'true' : 'false',
        'confidence_level' => knowledge_confidence($data['confidence_level'] ?? null),
        'source_id' => $data['source_id'] ?? null,
        'notes' => $data['notes'] ?? null,
    ];
    $stmt = db()->prepare(
        "SELECT id FROM knowledge_model_variants
         WHERE model_id = ? AND COALESCE(part_number, '') = COALESCE(?, '') AND COALESCE(order_number, '') = COALESCE(?, '')
           AND COALESCE(region, '') = COALESCE(?, '') AND variant_name = ?"
    );
    $stmt->execute([$data['model_id'], $fields['part_number'], $fields['order_number'], $fields['region'], $fields['variant_name']]);
    $existingId = $stmt->fetchColumn();
    if ($existingId) {
        $stmt = db()->prepare(
            'UPDATE knowledge_model_variants SET
                variant_name = :variant_name, configuration_type = :configuration_type, processor = :processor,
                cpu_cores = :cpu_cores, gpu = :gpu, gpu_cores = :gpu_cores, memory = :memory, storage = :storage,
                color = :color, part_number = :part_number, order_number = :order_number, region = :region,
                launch_price = :launch_price, currency = :currency, is_standard = :is_standard, is_cto = :is_cto,
                confidence_level = :confidence_level, source_id = :source_id, notes = :notes, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($fields + ['id' => $existingId]);
        return (int) $existingId;
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_model_variants
            (model_id, variant_name, configuration_type, processor, cpu_cores, gpu, gpu_cores, memory, storage,
             color, part_number, order_number, region, launch_price, currency, is_standard, is_cto, confidence_level, source_id, notes)
         VALUES (:model_id, :variant_name, :configuration_type, :processor, :cpu_cores, :gpu, :gpu_cores, :memory, :storage,
             :color, :part_number, :order_number, :region, :launch_price, :currency, :is_standard, :is_cto, :confidence_level, :source_id, :notes)
         RETURNING id'
    );
    $stmt->execute($fields + ['model_id' => $data['model_id']]);
    return (int) $stmt->fetchColumn();
}

function knowledge_logic_board_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_logic_boards WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_logic_board_find_by_code(string $boardCode, ?string $revision = null): ?array
{
    $stmt = db()->prepare("SELECT * FROM knowledge_logic_boards WHERE UPPER(board_code) = UPPER(?) AND COALESCE(UPPER(revision), '') = COALESCE(UPPER(?), '')");
    $stmt->execute([$boardCode, $revision]);
    return $stmt->fetch() ?: null;
}

function knowledge_logic_boards_all(?string $search = null): array
{
    $sql = 'SELECT * FROM knowledge_logic_boards WHERE 1 = 1';
    $params = [];
    if ($search) {
        $sql .= ' AND (board_code ILIKE :q OR full_code ILIKE :q OR board_id ILIKE :q OR description ILIKE :q)';
        $params['q'] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY board_code, revision LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function knowledge_logic_board_upsert(array $data): int
{
    $existing = knowledge_logic_board_find_by_code((string) $data['board_code'], $data['revision'] ?? null);
    $status = in_array($data['verification_status'] ?? '', KNOWLEDGE_AVAILABILITY_STATUSES, true) ? $data['verification_status'] : 'unverified';
    $fields = [
        'board_code' => strtoupper(trim((string) $data['board_code'])),
        'revision' => isset($data['revision']) && $data['revision'] !== '' ? strtoupper(trim((string) $data['revision'])) : null,
        'full_code' => $data['full_code'] ?? null,
        'board_id' => $data['board_id'] ?? null,
        'year' => $data['year'] ?? null,
        'architecture' => $data['architecture'] ?? null,
        'processor' => $data['processor'] ?? null,
        'gpu' => $data['gpu'] ?? null,
        'memory' => $data['memory'] ?? null,
        'description' => $data['description'] ?? null,
        'notes' => $data['notes'] ?? null,
        'confidence_level' => knowledge_confidence($data['confidence_level'] ?? null),
        'verification_status' => $status,
        'source_id' => $data['source_id'] ?? null,
    ];
    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE knowledge_logic_boards SET
                full_code = :full_code, board_id = :board_id, year = :year, architecture = :architecture,
                processor = :processor, gpu = :gpu, memory = :memory, description = :description, notes = :notes,
                confidence_level = :confidence_level, verification_status = :verification_status, source_id = :source_id,
                updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute(array_diff_key($fields, ['board_code' => true, 'revision' => true]) + ['id' => $existing['id']]);
        return (int) $existing['id'];
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_logic_boards
            (board_code, revision, full_code, board_id, year, architecture, processor, gpu, memory, description, notes,
             confidence_level, verification_status, source_id)
         VALUES (:board_code, :revision, :full_code, :board_id, :year, :architecture, :processor, :gpu, :memory, :description, :notes,
             :confidence_level, :verification_status, :source_id)
         RETURNING id'
    );
    $stmt->execute($fields);
    return (int) $stmt->fetchColumn();
}

function knowledge_model_logic_board_link(int $modelId, int $boardId, ?string $notes = null, ?int $sourceId = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO knowledge_model_logic_boards (model_id, board_id, compatibility_notes, source_id)
         VALUES (?, ?, ?, ?)
         ON CONFLICT (model_id, board_id) DO UPDATE SET compatibility_notes = EXCLUDED.compatibility_notes'
    );
    $stmt->execute([$modelId, $boardId, $notes, $sourceId]);
}

function knowledge_logic_boards_for_model(int $modelId): array
{
    $stmt = db()->prepare(
        'SELECT lb.*, mlb.compatibility_notes
         FROM knowledge_model_logic_boards mlb
         JOIN knowledge_logic_boards lb ON lb.id = mlb.board_id
         WHERE mlb.model_id = ? ORDER BY lb.board_code'
    );
    $stmt->execute([$modelId]);
    return $stmt->fetchAll();
}

function knowledge_models_for_board(int $boardId): array
{
    $stmt = db()->prepare(
        'SELECT km.*, mf.name AS manufacturer_name
         FROM knowledge_model_logic_boards mlb
         JOIN knowledge_models km ON km.id = mlb.model_id
         JOIN knowledge_manufacturers mf ON mf.id = km.manufacturer_id
         WHERE mlb.board_id = ? ORDER BY km.name'
    );
    $stmt->execute([$boardId]);
    return $stmt->fetchAll();
}

function knowledge_source_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_sources WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function knowledge_source_find_by_url(string $url): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_sources WHERE url = ?');
    $stmt->execute([$url]);
    return $stmt->fetch() ?: null;
}

function knowledge_sources_all(?string $search = null): array
{
    $sql = 'SELECT * FROM knowledge_sources WHERE 1 = 1';
    $params = [];
    if ($search) {
        $sql .= ' AND (title ILIKE :q OR organization ILIKE :q OR url ILIKE :q)';
        $params['q'] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function knowledge_source_upsert(array $data): int
{
    $existing = knowledge_source_find_by_url((string) $data['url']);
    $availability = in_array($data['availability_status'] ?? '', KNOWLEDGE_AVAILABILITY_STATUSES, true) ? $data['availability_status'] : 'unverified';
    $fields = [
        'title' => trim((string) $data['title']),
        'organization' => $data['organization'] ?? null,
        'url' => trim((string) $data['url']),
        'canonical_url' => $data['canonical_url'] ?? null,
        'source_type' => $data['source_type'] ?? null,
        'published_at' => $data['published_at'] ?? null,
        'checked_at' => $data['checked_at'] ?? date('Y-m-d'),
        'language' => $data['language'] ?? null,
        'license' => $data['license'] ?? null,
        'availability_status' => $availability,
        'confidence_level' => knowledge_confidence($data['confidence_level'] ?? 'fonte_secundaria'),
        'notes' => $data['notes'] ?? null,
    ];
    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE knowledge_sources SET
                title = :title, organization = :organization, canonical_url = :canonical_url, source_type = :source_type,
                published_at = :published_at, checked_at = :checked_at, language = :language, license = :license,
                availability_status = :availability_status, confidence_level = :confidence_level, notes = :notes
             WHERE id = :id'
        );
        $stmt->execute(array_diff_key($fields, ['url' => true]) + ['id' => $existing['id']]);
        return (int) $existing['id'];
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_sources
            (title, organization, url, canonical_url, source_type, published_at, checked_at, language, license,
             availability_status, confidence_level, notes)
         VALUES (:title, :organization, :url, :canonical_url, :source_type, :published_at, :checked_at, :language, :license,
             :availability_status, :confidence_level, :notes)
         RETURNING id'
    );
    $stmt->execute($fields);
    return (int) $stmt->fetchColumn();
}

function knowledge_source_evidence_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO knowledge_source_evidence (entity_type, entity_id, field_name, field_value, excerpt, source_id, checked_at, confidence_level, has_conflict)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON CONFLICT (entity_type, entity_id, field_name, source_id) DO UPDATE SET
            field_value = EXCLUDED.field_value, excerpt = EXCLUDED.excerpt, checked_at = EXCLUDED.checked_at,
            confidence_level = EXCLUDED.confidence_level, has_conflict = EXCLUDED.has_conflict
         RETURNING id'
    );
    $stmt->execute([
        $data['entity_type'], $data['entity_id'], $data['field_name'], $data['field_value'] ?? null,
        $data['excerpt'] ?? null, $data['source_id'], $data['checked_at'] ?? date('Y-m-d'),
        knowledge_confidence($data['confidence_level'] ?? null), !empty($data['has_conflict']) ? 'true' : 'false',
    ]);
    return (int) $stmt->fetchColumn();
}

function knowledge_source_evidence_for_entity(string $entityType, int $entityId): array
{
    $stmt = db()->prepare(
        'SELECT kse.*, ks.title AS source_title, ks.url AS source_url, ks.organization AS source_organization
         FROM knowledge_source_evidence kse
         JOIN knowledge_sources ks ON ks.id = kse.source_id
         WHERE kse.entity_type = ? AND kse.entity_id = ?
         ORDER BY kse.field_name'
    );
    $stmt->execute([$entityType, $entityId]);
    return $stmt->fetchAll();
}

function knowledge_os_compatibility_for_model(int $modelId): array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_os_compatibility WHERE model_id = ? ORDER BY os_version DESC');
    $stmt->execute([$modelId]);
    return $stmt->fetchAll();
}

function knowledge_os_compatibility_upsert(array $data): int
{
    $type = in_array($data['compatibility_type'] ?? '', KNOWLEDGE_OS_COMPATIBILITY_TYPES, true) ? $data['compatibility_type'] : 'original';
    $fields = [
        'os_name' => $data['os_name'] ?? 'macOS',
        'os_version' => trim((string) $data['os_version']),
        'build' => $data['build'] ?? null,
        'compatibility_type' => $type,
        'is_official' => !empty($data['is_official']) || !array_key_exists('is_official', $data) ? 'true' : 'false',
        'required_tool' => $data['required_tool'] ?? null,
        'limitations' => $data['limitations'] ?? null,
        'checked_at' => $data['checked_at'] ?? date('Y-m-d'),
        'source_id' => $data['source_id'] ?? null,
        'confidence_level' => knowledge_confidence($data['confidence_level'] ?? 'fonte_secundaria'),
    ];
    $stmt = db()->prepare(
        'SELECT id FROM knowledge_os_compatibility WHERE model_id = ? AND os_name = ? AND os_version = ? AND compatibility_type = ?'
    );
    $stmt->execute([$data['model_id'], $fields['os_name'], $fields['os_version'], $fields['compatibility_type']]);
    $existingId = $stmt->fetchColumn();
    if ($existingId) {
        $stmt = db()->prepare(
            'UPDATE knowledge_os_compatibility SET
                build = :build, is_official = :is_official, required_tool = :required_tool, limitations = :limitations,
                checked_at = :checked_at, source_id = :source_id, confidence_level = :confidence_level
             WHERE id = :id'
        );
        $stmt->execute(array_diff_key($fields, ['os_name' => true, 'os_version' => true, 'compatibility_type' => true]) + ['id' => $existingId]);
        return (int) $existingId;
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_os_compatibility
            (model_id, os_name, os_version, build, compatibility_type, is_official, required_tool, limitations,
             checked_at, source_id, confidence_level)
         VALUES (:model_id, :os_name, :os_version, :build, :compatibility_type, :is_official, :required_tool, :limitations,
             :checked_at, :source_id, :confidence_level)
         RETURNING id'
    );
    $stmt->execute($fields + ['model_id' => $data['model_id']]);
    return (int) $stmt->fetchColumn();
}

function knowledge_service_programs_for_model(int $modelId): array
{
    $stmt = db()->prepare(
        'SELECT sp.* FROM knowledge_service_program_models spm
         JOIN knowledge_service_programs sp ON sp.id = spm.service_program_id
         WHERE spm.model_id = ? ORDER BY sp.period_start DESC NULLS LAST'
    );
    $stmt->execute([$modelId]);
    return $stmt->fetchAll();
}

function knowledge_service_program_upsert(array $data): int
{
    $fields = [
        'name' => trim((string) $data['name']),
        'symptom' => $data['symptom'] ?? null,
        'eligibility' => $data['eligibility'] ?? null,
        'period_start' => $data['period_start'] ?? null,
        'period_end' => $data['period_end'] ?? null,
        'region' => $data['region'] ?? null,
        'solution' => $data['solution'] ?? null,
        'status' => in_array($data['status'] ?? '', ['ativo','encerrado','desconhecido'], true) ? $data['status'] : 'desconhecido',
        'official_url' => $data['official_url'] ?? null,
        'checked_at' => $data['checked_at'] ?? date('Y-m-d'),
        'source_id' => $data['source_id'] ?? null,
    ];
    $existingId = null;
    if ($fields['official_url']) {
        $stmt = db()->prepare('SELECT id FROM knowledge_service_programs WHERE official_url = ?');
        $stmt->execute([$fields['official_url']]);
        $existingId = $stmt->fetchColumn() ?: null;
    }
    if ($existingId) {
        $stmt = db()->prepare(
            'UPDATE knowledge_service_programs SET
                name = :name, symptom = :symptom, eligibility = :eligibility, period_start = :period_start,
                period_end = :period_end, region = :region, solution = :solution, status = :status,
                checked_at = :checked_at, source_id = :source_id, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute(array_diff_key($fields, ['official_url' => true]) + ['id' => $existingId]);
        $id = (int) $existingId;
    } else {
        $stmt = db()->prepare(
            'INSERT INTO knowledge_service_programs
                (name, symptom, eligibility, period_start, period_end, region, solution, status, official_url, checked_at, source_id)
             VALUES (:name, :symptom, :eligibility, :period_start, :period_end, :region, :solution, :status, :official_url, :checked_at, :source_id)
             RETURNING id'
        );
        $stmt->execute($fields);
        $id = (int) $stmt->fetchColumn();
    }
    foreach ((array) ($data['model_ids'] ?? []) as $modelId) {
        $stmt = db()->prepare('INSERT INTO knowledge_service_program_models (service_program_id, model_id) VALUES (?, ?) ON CONFLICT DO NOTHING');
        $stmt->execute([$id, (int) $modelId]);
    }
    return $id;
}

function knowledge_import_run_create(string $mode, array $params = []): int
{
    $stmt = db()->prepare('INSERT INTO knowledge_import_runs (mode, params) VALUES (?, ?::jsonb) RETURNING id');
    $stmt->execute([$mode, json_encode($params, JSON_UNESCAPED_UNICODE)]);
    return (int) $stmt->fetchColumn();
}

function knowledge_import_run_finish(int $runId, string $status, array $summary = []): void
{
    $stmt = db()->prepare('UPDATE knowledge_import_runs SET finished_at = NOW(), status = ?, summary = ?::jsonb WHERE id = ?');
    $stmt->execute([$status, json_encode($summary, JSON_UNESCAPED_UNICODE), $runId]);
}

function knowledge_import_item_find(int $runId, string $stagingKey): ?array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_import_items WHERE run_id = ? AND staging_key = ?');
    $stmt->execute([$runId, $stagingKey]);
    return $stmt->fetch() ?: null;
}

function knowledge_import_item_upsert(int $runId, string $stagingKey, string $entityType, string $action, ?string $dedupKey, string $status, ?string $error = null, ?int $createdEntityId = null): int
{
    $existing = knowledge_import_item_find($runId, $stagingKey);
    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE knowledge_import_items SET action = ?, dedup_key = ?, status = ?, error = ?, created_entity_id = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$action, $dedupKey, $status, $error, $createdEntityId, $existing['id']]);
        return (int) $existing['id'];
    }
    $stmt = db()->prepare(
        'INSERT INTO knowledge_import_items (run_id, staging_key, entity_type, action, dedup_key, status, error, created_entity_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([$runId, $stagingKey, $entityType, $action, $dedupKey, $status, $error, $createdEntityId]);
    return (int) $stmt->fetchColumn();
}

function knowledge_review_queue_create(string $entityType, array $payload, string $reason, ?int $conflictWithId = null): int
{
    $stmt = db()->prepare(
        'INSERT INTO knowledge_review_queue (entity_type, payload, reason, conflict_with_id) VALUES (?, ?::jsonb, ?, ?) RETURNING id'
    );
    $stmt->execute([$entityType, json_encode($payload, JSON_UNESCAPED_UNICODE), $reason, $conflictWithId]);
    return (int) $stmt->fetchColumn();
}

function knowledge_review_queue_all(string $status = 'pendente'): array
{
    $stmt = db()->prepare('SELECT * FROM knowledge_review_queue WHERE status = ? ORDER BY created_at DESC LIMIT 200');
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

function knowledge_review_queue_set_status(int $id, string $status, int $userId): void
{
    $stmt = db()->prepare('UPDATE knowledge_review_queue SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE id = ?');
    $stmt->execute([$status, $userId, $id]);
}
