-- =============================================================
-- iFixAdmin — Centro de Conhecimento: expansão do catálogo Apple
-- Identificadores, variantes, placas lógicas, fontes/evidências,
-- compatibilidade macOS, programas de serviço e controle de importação.
-- Idempotente e sem remoção de dados. Não altera 003/004/005/007/008.
-- =============================================================

BEGIN;

-- ---------- knowledge_models: campos e flexibilização de unicidade ----------

ALTER TABLE knowledge_models ADD COLUMN IF NOT EXISTS family VARCHAR(60);
ALTER TABLE knowledge_models ADD COLUMN IF NOT EXISTS release_year SMALLINT;
ALTER TABLE knowledge_models ADD COLUMN IF NOT EXISTS release_period VARCHAR(40);
ALTER TABLE knowledge_models ADD COLUMN IF NOT EXISTS architecture VARCHAR(30);
ALTER TABLE knowledge_models ADD COLUMN IF NOT EXISTS confidence_level VARCHAR(30) NOT NULL DEFAULT 'nao_verificado'
    CHECK (confidence_level IN ('oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado'));

-- O mesmo A-number pode corresponder a mais de um lançamento oficial (regra da seção 2.2 do
-- pedido). As constraints abaixo impediam isso; a dedupe real passa para a camada de aplicação
-- (knowledge_model_dedupe_key), usando fabricante + nome + ano + identifiers.
ALTER TABLE knowledge_models DROP CONSTRAINT IF EXISTS knowledge_models_manufacturer_id_code_key;
DROP INDEX IF EXISTS idx_knowledge_models_normalized_code;
CREATE INDEX IF NOT EXISTS idx_knowledge_models_normalized_code ON knowledge_models (manufacturer_id, normalized_code);
CREATE INDEX IF NOT EXISTS idx_knowledge_models_family ON knowledge_models (family, release_year);

-- ---------- Identificadores dos modelos ----------

CREATE TABLE IF NOT EXISTS knowledge_model_identifiers (
    id                SERIAL PRIMARY KEY,
    model_id          INTEGER NOT NULL REFERENCES knowledge_models(id) ON DELETE CASCADE,
    identifier_type   VARCHAR(30) NOT NULL
                      CHECK (identifier_type IN ('model_number','model_identifier','emc','order_number',
                             'part_number','marketing_part_number','board_id','internal_identifier','serial_prefix')),
    identifier_value  VARCHAR(80) NOT NULL,
    region            VARCHAR(40),
    is_primary        BOOLEAN NOT NULL DEFAULT FALSE,
    confidence_level  VARCHAR(30) NOT NULL DEFAULT 'nao_verificado'
                      CHECK (confidence_level IN ('oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado')),
    source_id         INTEGER,
    notes             TEXT,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_model_identifiers_dedupe
    ON knowledge_model_identifiers (model_id, identifier_type, UPPER(identifier_value), COALESCE(region, ''));
CREATE INDEX IF NOT EXISTS idx_knowledge_model_identifiers_value ON knowledge_model_identifiers (UPPER(identifier_value));
CREATE INDEX IF NOT EXISTS idx_knowledge_model_identifiers_model ON knowledge_model_identifiers (model_id, identifier_type);

-- ---------- Variantes / configurações ----------

CREATE TABLE IF NOT EXISTS knowledge_model_variants (
    id                 SERIAL PRIMARY KEY,
    model_id           INTEGER NOT NULL REFERENCES knowledge_models(id) ON DELETE CASCADE,
    variant_name       VARCHAR(160) NOT NULL,
    configuration_type VARCHAR(20) NOT NULL DEFAULT 'standard'
                       CHECK (configuration_type IN ('standard','cto','bto','regional','revision')),
    processor          VARCHAR(120),
    cpu_cores          SMALLINT,
    gpu                VARCHAR(120),
    gpu_cores          SMALLINT,
    memory             VARCHAR(60),
    storage            VARCHAR(60),
    color              VARCHAR(40),
    part_number        VARCHAR(40),
    order_number       VARCHAR(40),
    region             VARCHAR(40),
    launch_price       NUMERIC(12,2),
    currency           VARCHAR(10),
    is_standard        BOOLEAN NOT NULL DEFAULT TRUE,
    is_cto             BOOLEAN NOT NULL DEFAULT FALSE,
    confidence_level   VARCHAR(30) NOT NULL DEFAULT 'nao_verificado'
                       CHECK (confidence_level IN ('oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado')),
    source_id          INTEGER,
    notes              TEXT,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_model_variants_dedupe
    ON knowledge_model_variants (model_id, COALESCE(part_number, ''), COALESCE(order_number, ''), COALESCE(region, ''), variant_name);
CREATE INDEX IF NOT EXISTS idx_knowledge_model_variants_model ON knowledge_model_variants (model_id);

-- ---------- Placas lógicas ----------

CREATE TABLE IF NOT EXISTS knowledge_logic_boards (
    id                  SERIAL PRIMARY KEY,
    board_code          VARCHAR(30) NOT NULL,
    revision            VARCHAR(10),
    full_code           VARCHAR(50),
    board_id            VARCHAR(60),
    year                SMALLINT,
    architecture        VARCHAR(30),
    processor           VARCHAR(120),
    gpu                 VARCHAR(120),
    memory              VARCHAR(60),
    description         TEXT,
    notes               TEXT,
    confidence_level    VARCHAR(30) NOT NULL DEFAULT 'nao_verificado'
                        CHECK (confidence_level IN ('oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado')),
    verification_status VARCHAR(30) NOT NULL DEFAULT 'unverified'
                        CHECK (verification_status IN ('available_official','available_licensed','external_reference_only',
                               'not_found','legally_unavailable','source_restricted','unverified','blocked_suspicious_source')),
    source_id           INTEGER,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_logic_boards_dedupe
    ON knowledge_logic_boards (UPPER(board_code), COALESCE(UPPER(revision), ''));
CREATE INDEX IF NOT EXISTS idx_knowledge_logic_boards_code ON knowledge_logic_boards (UPPER(board_code));

CREATE TABLE IF NOT EXISTS knowledge_model_logic_boards (
    id                   SERIAL PRIMARY KEY,
    model_id             INTEGER NOT NULL REFERENCES knowledge_models(id) ON DELETE CASCADE,
    board_id             INTEGER NOT NULL REFERENCES knowledge_logic_boards(id) ON DELETE CASCADE,
    compatibility_notes  TEXT,
    source_id            INTEGER,
    created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (model_id, board_id)
);
CREATE INDEX IF NOT EXISTS idx_knowledge_model_logic_boards_model ON knowledge_model_logic_boards (model_id);
CREATE INDEX IF NOT EXISTS idx_knowledge_model_logic_boards_board ON knowledge_model_logic_boards (board_id);

-- ---------- Fontes e evidências ----------

CREATE TABLE IF NOT EXISTS knowledge_sources (
    id                  SERIAL PRIMARY KEY,
    title               VARCHAR(220) NOT NULL,
    organization        VARCHAR(120),
    url                 VARCHAR(600) NOT NULL,
    canonical_url       VARCHAR(600),
    source_type         VARCHAR(40),
    published_at        DATE,
    checked_at          DATE,
    language            VARCHAR(10),
    license             VARCHAR(80),
    availability_status VARCHAR(30) NOT NULL DEFAULT 'unverified'
                        CHECK (availability_status IN ('available_official','available_licensed','external_reference_only',
                               'not_found','legally_unavailable','source_restricted','unverified','blocked_suspicious_source')),
    confidence_level    VARCHAR(30) NOT NULL DEFAULT 'fonte_secundaria'
                        CHECK (confidence_level IN ('oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado')),
    notes               TEXT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_sources_url ON knowledge_sources (url);

CREATE TABLE IF NOT EXISTS knowledge_source_evidence (
    id               SERIAL PRIMARY KEY,
    entity_type      VARCHAR(30) NOT NULL
                     CHECK (entity_type IN ('model','identifier','variant','logic_board','article',
                            'os_compatibility','service_program','asset','schematic')),
    entity_id        INTEGER NOT NULL,
    field_name       VARCHAR(80) NOT NULL,
    field_value      TEXT,
    excerpt          TEXT,
    source_id        INTEGER NOT NULL REFERENCES knowledge_sources(id) ON DELETE CASCADE,
    checked_at       DATE,
    confidence_level VARCHAR(30) NOT NULL DEFAULT 'fonte_secundaria'
                     CHECK (confidence_level IN ('oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado')),
    has_conflict     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_knowledge_source_evidence_entity ON knowledge_source_evidence (entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_knowledge_source_evidence_source ON knowledge_source_evidence (source_id);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_source_evidence_dedupe
    ON knowledge_source_evidence (entity_type, entity_id, field_name, source_id);

-- Referências de source_id criadas antes de knowledge_sources existir (identifiers/variants/boards).
ALTER TABLE knowledge_model_identifiers DROP CONSTRAINT IF EXISTS fk_knowledge_model_identifiers_source;
ALTER TABLE knowledge_model_identifiers ADD CONSTRAINT fk_knowledge_model_identifiers_source
    FOREIGN KEY (source_id) REFERENCES knowledge_sources(id) ON DELETE SET NULL;
ALTER TABLE knowledge_model_variants DROP CONSTRAINT IF EXISTS fk_knowledge_model_variants_source;
ALTER TABLE knowledge_model_variants ADD CONSTRAINT fk_knowledge_model_variants_source
    FOREIGN KEY (source_id) REFERENCES knowledge_sources(id) ON DELETE SET NULL;
ALTER TABLE knowledge_logic_boards DROP CONSTRAINT IF EXISTS fk_knowledge_logic_boards_source;
ALTER TABLE knowledge_logic_boards ADD CONSTRAINT fk_knowledge_logic_boards_source
    FOREIGN KEY (source_id) REFERENCES knowledge_sources(id) ON DELETE SET NULL;
ALTER TABLE knowledge_model_logic_boards DROP CONSTRAINT IF EXISTS fk_knowledge_model_logic_boards_source;
ALTER TABLE knowledge_model_logic_boards ADD CONSTRAINT fk_knowledge_model_logic_boards_source
    FOREIGN KEY (source_id) REFERENCES knowledge_sources(id) ON DELETE SET NULL;

-- ---------- Compatibilidade de sistema ----------

CREATE TABLE IF NOT EXISTS knowledge_os_compatibility (
    id                  SERIAL PRIMARY KEY,
    model_id            INTEGER NOT NULL REFERENCES knowledge_models(id) ON DELETE CASCADE,
    os_name             VARCHAR(40) NOT NULL DEFAULT 'macOS',
    os_version          VARCHAR(60) NOT NULL,
    build               VARCHAR(30),
    compatibility_type  VARCHAR(30) NOT NULL
                        CHECK (compatibility_type IN ('original','minimum','maximum_official','currently_supported',
                               'security_updates','boot_camp','unofficial_oclp')),
    is_official         BOOLEAN NOT NULL DEFAULT TRUE,
    required_tool       VARCHAR(120),
    limitations         TEXT,
    checked_at          DATE,
    source_id           INTEGER REFERENCES knowledge_sources(id) ON DELETE SET NULL,
    confidence_level    VARCHAR(30) NOT NULL DEFAULT 'fonte_secundaria'
                        CHECK (confidence_level IN ('oficial','multiplas_fontes','fonte_secundaria','comunitario','conflito','nao_verificado')),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_os_compatibility_dedupe
    ON knowledge_os_compatibility (model_id, os_name, os_version, compatibility_type);
CREATE INDEX IF NOT EXISTS idx_knowledge_os_compatibility_model ON knowledge_os_compatibility (model_id);

-- ---------- Programas oficiais de serviço ----------

CREATE TABLE IF NOT EXISTS knowledge_service_programs (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(220) NOT NULL,
    symptom       TEXT,
    eligibility   TEXT,
    period_start  DATE,
    period_end    DATE,
    region        VARCHAR(60),
    solution      TEXT,
    status        VARCHAR(20) NOT NULL DEFAULT 'ativo' CHECK (status IN ('ativo','encerrado','desconhecido')),
    official_url  VARCHAR(600),
    checked_at    DATE,
    source_id     INTEGER REFERENCES knowledge_sources(id) ON DELETE SET NULL,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_service_programs_url ON knowledge_service_programs (official_url) WHERE official_url IS NOT NULL;

CREATE TABLE IF NOT EXISTS knowledge_service_program_models (
    id                  SERIAL PRIMARY KEY,
    service_program_id  INTEGER NOT NULL REFERENCES knowledge_service_programs(id) ON DELETE CASCADE,
    model_id             INTEGER NOT NULL REFERENCES knowledge_models(id) ON DELETE CASCADE,
    UNIQUE (service_program_id, model_id)
);
CREATE INDEX IF NOT EXISTS idx_knowledge_service_program_models_model ON knowledge_service_program_models (model_id);

-- ---------- Controle de importação ----------

CREATE TABLE IF NOT EXISTS knowledge_import_runs (
    id          SERIAL PRIMARY KEY,
    started_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    finished_at TIMESTAMPTZ,
    mode        VARCHAR(20) NOT NULL DEFAULT 'dry_run' CHECK (mode IN ('dry_run','validate_only','commit')),
    params      JSONB,
    status      VARCHAR(20) NOT NULL DEFAULT 'running' CHECK (status IN ('running','completed','failed','aborted')),
    summary     JSONB
);

CREATE TABLE IF NOT EXISTS knowledge_import_items (
    id                SERIAL PRIMARY KEY,
    run_id            INTEGER NOT NULL REFERENCES knowledge_import_runs(id) ON DELETE CASCADE,
    staging_key       VARCHAR(220) NOT NULL,
    entity_type       VARCHAR(30) NOT NULL,
    action            VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (action IN ('insert','update','skip','pending')),
    dedup_key         VARCHAR(400),
    status            VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','done','error','review')),
    error             TEXT,
    created_entity_id INTEGER,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_knowledge_import_items_run ON knowledge_import_items (run_id, status);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_import_items_key ON knowledge_import_items (run_id, staging_key);

CREATE TABLE IF NOT EXISTS knowledge_review_queue (
    id               SERIAL PRIMARY KEY,
    entity_type      VARCHAR(30) NOT NULL,
    payload          JSONB NOT NULL,
    reason           TEXT NOT NULL,
    conflict_with_id INTEGER,
    status           VARCHAR(20) NOT NULL DEFAULT 'pendente' CHECK (status IN ('pendente','resolvido','descartado')),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    resolved_at      TIMESTAMPTZ,
    resolved_by      INTEGER REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_knowledge_review_queue_status ON knowledge_review_queue (status, created_at DESC);

COMMIT;
